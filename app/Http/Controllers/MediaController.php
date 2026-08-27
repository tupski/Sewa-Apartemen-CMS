<?php

namespace App\Http\Controllers;

use App\Models\Media;
use App\Http\Requests\MediaRequest;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class MediaController extends Controller
{
    /**
     * Allowed MIME types for uploads (shared by file upload + URL import).
     *
     * @var array<string, string>  mime => extension
     */
    protected array $allowedMimes = [
        'image/jpeg'      => 'jpg',
        'image/png'       => 'png',
        'image/webp'      => 'webp',
        'image/gif'       => 'gif',
        'image/svg+xml'   => 'svg',
        'application/pdf' => 'pdf',
    ];

    /**
     * Max download size for URL imports (bytes). 10MB.
     */
    protected int $maxUrlBytes = 10 * 1024 * 1024;

    /**
     * Create a new controller instance.
     */
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Display a listing of media files.
     */
    public function index(Request $request)
    {
        $query = Media::with('user');

        if ($request->filled('folder')) {
            $query->where('directory', $request->folder);
        }

        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('filename', 'like', '%' . $search . '%')
                  ->orWhere('original_filename', 'like', '%' . $search . '%')
                  ->orWhere('title', 'like', '%' . $search . '%')
                  ->orWhere('alt', 'like', '%' . $search . '%');
            });
        }

        $media = $query->orderBy('created_at', 'desc')->paginate(24)->withQueryString();
        $folders = $this->getFolders();

        // JSON response for the "Media Library" picker tab (AJAX browsing).
        if ($request->wantsJson() || $request->boolean('json')) {
            return response()->json([
                'data' => $media->getCollection()->map(fn (Media $m) => $this->toArray($m))->values(),
                'meta' => [
                    'current_page' => $media->currentPage(),
                    'last_page'    => $media->lastPage(),
                    'total'        => $media->total(),
                ],
            ]);
        }

        return view('admin.media.index', compact('media', 'folders'));
    }

    /**
     * Show the form for creating a new media file.
     */
    public function create()
    {
        return view('admin.media.create');
    }

    /**
     * Store a newly created media file (single-file, classic form).
     */
    public function store(MediaRequest $request)
    {
        try {
            $validated = $request->validated();
            $media = $this->persistUploadedFile($request->file('file'), [
                'alt'         => $validated['alt'] ?? null,
                'title'       => $validated['title'] ?? null,
                'caption'     => $validated['caption'] ?? null,
                'description' => $validated['description'] ?? null,
            ]);

            if ($request->wantsJson()) {
                return response()->json(['media' => $this->toArray($media)], 201);
            }

            return back()->with('success', __('media.upload_success'));
        } catch (\Throwable $e) {
            if ($request->wantsJson()) {
                return response()->json(['message' => $e->getMessage()], 422);
            }

            return back()->withInput()->with('error', __('media.upload_failed') . ' ' . $e->getMessage());
        }
    }

    /**
     * Handle multiple-file uploads from the "Add Media" modal (drag & drop / picker).
     * Returns JSON so the front-end can show per-file progress/results.
     */
    public function upload(Request $request)
    {
        $request->validate([
            'files'   => ['required', 'array'],
            'files.*' => ['required', 'file', 'max:10240', 'mimes:jpg,jpeg,png,webp,gif,svg,pdf'],
        ]);

        $created = [];
        $errors = [];

        foreach ($request->file('files', []) as $file) {
            try {
                $media = $this->persistUploadedFile($file);
                $created[] = $this->toArray($media);
            } catch (\Throwable $e) {
                $errors[] = [
                    'file'    => $file->getClientOriginalName(),
                    'message' => $e->getMessage(),
                ];
            }
        }

        if ($request->wantsJson()) {
            return response()->json([
                'uploaded' => $created,
                'errors'   => $errors,
            ], $created ? 201 : 422);
        }

        if ($errors && ! $created) {
            return back()->with('error', __('media.upload_failed'));
        }

        return back()->with('success', __('media.upload_success'));
    }

    /**
     * Import a media file from a remote URL (WordPress-style "Upload from URL").
     *
     * Security measures:
     *   - scheme allowlist (http / https only)
     *   - SSRF guard: blocks private, loopback, and link-local IP ranges
     *   - download size cap (10MB) enforced while streaming
     *   - connection + total timeout
     *   - content-type verified against an allowlist AFTER download
     */
    public function fromUrl(Request $request)
    {
        $validated = $request->validate([
            'url'         => ['required', 'string', 'max:2048', 'url'],
            'alt'         => ['nullable', 'string', 'max:255'],
            'title'       => ['nullable', 'string', 'max:255'],
            'caption'     => ['nullable', 'string', 'max:500'],
            'description' => ['nullable', 'string'],
        ]);

        try {
            [$binary, $contentType] = $this->downloadFromUrl($validated['url']);

            $extension = $this->allowedMimes[$contentType] ?? null;
            if (! $extension) {
                throw new \RuntimeException(__('media.url_bad_type'));
            }

            // Write to a temp file and wrap in an UploadedFile so we reuse the
            // exact same storage + naming + thumbnail pipeline as normal uploads.
            $tmpPath = tempnam(sys_get_temp_dir(), 'mediaurl_');
            file_put_contents($tmpPath, $binary);

            $originalName = $this->filenameFromUrl($validated['url'], $extension);

            $uploaded = new UploadedFile(
                $tmpPath,
                $originalName,
                $contentType,
                null,
                true // test mode = trust the given path (already validated server-side)
            );

            $media = $this->persistUploadedFile($uploaded, [
                'alt'               => $validated['alt'] ?? null,
                'title'             => $validated['title'] ?? null,
                'caption'           => $validated['caption'] ?? null,
                'description'       => $validated['description'] ?? null,
                'original_filename' => $originalName,
                'mime_type'         => $contentType,
                'size'              => strlen($binary),
            ]);

            @unlink($tmpPath);

            if ($request->wantsJson()) {
                return response()->json(['media' => $this->toArray($media)], 201);
            }

            return back()->with('success', __('media.upload_success'));
        } catch (\Throwable $e) {
            if ($request->wantsJson()) {
                return response()->json(['message' => $e->getMessage()], 422);
            }

            return back()->withInput()->with('error', __('media.upload_failed') . ' ' . $e->getMessage());
        }
    }

    /**
     * Display the specified media file.
     */
    public function show(Media $media)
    {
        return view('admin.media.show', compact('media'));
    }

    /**
     * Show the form for editing the specified media file.
     */
    public function edit(Media $media)
    {
        return view('admin.media.edit', compact('media'));
    }

    /**
     * Update metadata (title/alt/caption/description) of a media record.
     */
    public function update(Request $request, Media $media)
    {
        try {
            $validated = $request->validate([
                'alt'         => 'nullable|string|max:255',
                'title'       => 'nullable|string|max:255',
                'caption'     => 'nullable|string|max:500',
                'description' => 'nullable|string',
            ]);

            $media->update([
                'alt'         => $validated['alt'] ?? null,
                'title'       => $validated['title'] ?? null,
                'caption'     => $validated['caption'] ?? null,
                'description' => $validated['description'] ?? null,
            ]);

            if ($request->wantsJson()) {
                return response()->json(['media' => $this->toArray($media->fresh())]);
            }

            return back()->with('success', __('media.update_success'));
        } catch (\Throwable $e) {
            if ($request->wantsJson()) {
                return response()->json(['message' => $e->getMessage()], 422);
            }

            return back()->withInput()->with('error', __('media.update_failed') . ' ' . $e->getMessage());
        }
    }

    /**
     * Remove the specified media file.
     */
    public function destroy(Request $request, Media $media)
    {
        try {
            $media->deleteFile();
            $media->delete();

            if ($request->wantsJson()) {
                return response()->json(['deleted' => true]);
            }

            return redirect()->route('admin.media.index')
                ->with('success', __('media.delete_success'));
        } catch (\Throwable $e) {
            if ($request->wantsJson()) {
                return response()->json(['message' => $e->getMessage()], 422);
            }

            return back()->with('error', __('media.delete_failed') . ' ' . $e->getMessage());
        }
    }

    // ─────────────────────────────────────────────────────────────────────
    // Internal helpers
    // ─────────────────────────────────────────────────────────────────────

    /**
     * Persist an UploadedFile to storage and create the Media record.
     * Shared by store(), upload() and fromUrl() for consistency.
     *
     * @param  array<string, mixed>  $meta  metadata overrides
     */
    protected function persistUploadedFile(UploadedFile $file, array $meta = []): Media
    {
        $mimeType = $meta['mime_type'] ?? $file->getMimeType();
        $type = $this->getFileType($mimeType);
        $originalName = $meta['original_filename'] ?? $file->getClientOriginalName();

        $uploadContext = $meta['alt'] ?? $meta['title'] ?? 'media';
        $result = upload_file($file, [
            'base_folder'   => 'Media',
            'sub_folders'   => [date('Y'), date('m')],
            'name_prefix'   => 'Media',
            'name_category' => $uploadContext,
        ]);

        $filename  = $result['filename'];
        $extension = $result['extension'];
        $folder    = $result['folder'];

        $width = null;
        $height = null;
        if ($type === 'image' && $mimeType !== 'image/svg+xml') {
            $imageInfo = @getimagesize($file->getRealPath());
            if ($imageInfo) {
                $width = $imageInfo[0];
                $height = $imageInfo[1];
            }
            $this->generateThumbnail($file->getRealPath(), $folder, $filename);
        }

        return Media::create([
            'user_id'           => auth()->id(),
            'disk'              => 'public',
            'directory'         => $folder,
            'filename'          => $filename,
            'original_filename' => $originalName,
            'mime_type'         => $mimeType,
            'extension'         => $extension,
            'size'              => $meta['size'] ?? $file->getSize(),
            'width'             => $width,
            'height'            => $height,
            'type'              => $type,
            'alt'               => $meta['alt'] ?? null,
            'title'             => $meta['title'] ?? null,
            'caption'           => $meta['caption'] ?? null,
            'description'       => $meta['description'] ?? null,
        ]);
    }

    /**
     * Download a remote file with SSRF / size / timeout guards.
     *
     * @return array{0: string, 1: string}  [binary, contentType]
     *
     * @throws \RuntimeException on any guard violation or transport failure
     */
    protected function downloadFromUrl(string $url): array
    {
        $parts = parse_url($url);
        $scheme = strtolower($parts['scheme'] ?? '');
        $host = $parts['host'] ?? '';

        if (! in_array($scheme, ['http', 'https'], true)) {
            throw new \RuntimeException(__('media.url_bad_scheme'));
        }
        if ($host === '') {
            throw new \RuntimeException(__('media.url_invalid'));
        }

        // parse_url() keeps the brackets on IPv6 literals ("[::1]"); strip them
        // for validation and for the CURLOPT_RESOLVE name field.
        $bareHost = trim($host, '[]');
        $port = (int) ($parts['port'] ?? ($scheme === 'https' ? 443 : 80));

        // ── SSRF guard: resolve host and reject private/loopback/link-local IPs ──
        $pinnedIp = $this->assertPublicHost($bareHost);

        if (! function_exists('curl_init')) {
            throw new \RuntimeException('cURL extension is required for URL import.');
        }

        $ch = curl_init();
        $maxBytes = $this->maxUrlBytes;

        curl_setopt_array($ch, $this->buildCurlOptions($url, $bareHost, $port, $pinnedIp, $maxBytes));

        $body = curl_exec($ch);
        $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $contentType = (string) curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
        $errno = curl_errno($ch);
        curl_close($ch);

        if ($errno === CURLE_FILESIZE_EXCEEDED || $errno === CURLE_ABORTED_BY_CALLBACK) {
            throw new \RuntimeException(__('media.url_too_large'));
        }
        if ($body === false || $errno !== 0) {
            throw new \RuntimeException(__('media.url_fetch_failed'));
        }
        if ($httpCode < 200 || $httpCode >= 300) {
            throw new \RuntimeException(__('media.url_fetch_failed') . ' (HTTP ' . $httpCode . ')');
        }
        if (strlen($body) > $maxBytes) {
            throw new \RuntimeException(__('media.url_too_large'));
        }

        // Normalize content-type (strip charset etc.) and prefer sniffed type.
        $contentType = trim(explode(';', $contentType)[0]);
        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $sniffed = $finfo->buffer($body) ?: $contentType;

        // For non-SVG, trust the sniffed type; SVG is text/xml so keep header hint.
        if (isset($this->allowedMimes[$sniffed])) {
            $contentType = $sniffed;
        } elseif ($sniffed === 'image/svg' ) {
            $contentType = 'image/svg+xml';
        }

        return [$body, $contentType];
    }

    /**
     * Build the curl option set for a URL import.
     *
     * Split out from {@see downloadFromUrl()} so the SSRF-relevant options
     * (notably CURLOPT_RESOLVE pinning) are directly assertable in tests.
     *
     * @return array<int, mixed>
     */
    protected function buildCurlOptions(
        string $url,
        string $host,
        int $port,
        string $pinnedIp,
        int $maxBytes
    ): array {
        $options = [
            CURLOPT_URL            => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => false, // do not follow redirects (SSRF safety)
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_TIMEOUT        => 20,
            CURLOPT_MAXFILESIZE    => $maxBytes,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
            CURLOPT_USERAGENT      => 'SewaApartemenCMS-MediaImporter/1.0',
            // Abort mid-stream if the response body exceeds the cap.
            CURLOPT_NOPROGRESS     => false,
            CURLOPT_PROGRESSFUNCTION => function ($res, $dlTotal, $dlNow) use ($maxBytes) {
                return ($dlTotal > $maxBytes || $dlNow > $maxBytes) ? 1 : 0;
            },
        ];

        // SEC-08: close the DNS-rebinding window. assertPublicHost() resolved
        // and validated an IP; pin it so curl cannot perform its own lookup and
        // land on a different (private) address. Literal-IP URLs need no entry
        // because there is no name for a hostile resolver to re-answer.
        if (! filter_var($host, FILTER_VALIDATE_IP)) {
            $target = filter_var($pinnedIp, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6) !== false
                ? '[' . $pinnedIp . ']'
                : $pinnedIp;

            $options[CURLOPT_RESOLVE] = [$host . ':' . $port . ':' . $target];
        }

        return $options;
    }

    /**
     * Reject hosts that resolve to private, loopback, link-local, CGNAT,
     * metadata-service, or otherwise non-routable addresses.
     *
     * Every address the host resolves to must pass; the first one is returned
     * so the caller can pin it via CURLOPT_RESOLVE.
     *
     * @return string the validated IP to connect to
     *
     * @throws \RuntimeException
     */
    protected function assertPublicHost(string $host): string
    {
        // If host is a literal IP, validate it directly.
        $ips = [];
        if (filter_var($host, FILTER_VALIDATE_IP)) {
            $ips[] = $host;
        } else {
            // Resolve both A and AAAA records.
            $records = @dns_get_record($host, DNS_A + DNS_AAAA) ?: [];
            foreach ($records as $r) {
                if (! empty($r['ip'])) {
                    $ips[] = $r['ip'];
                }
                if (! empty($r['ipv6'])) {
                    $ips[] = $r['ipv6'];
                }
            }
            // Fallback for A record if dns_get_record is limited.
            if (! $ips) {
                $resolved = gethostbyname($host);
                if ($resolved && $resolved !== $host) {
                    $ips[] = $resolved;
                }
            }
        }

        if (! $ips) {
            throw new \RuntimeException(__('media.url_resolve_failed'));
        }

        foreach ($ips as $ip) {
            if ($this->isBlockedIp($ip)) {
                throw new \RuntimeException(__('media.url_blocked_host'));
            }
        }

        return $ips[0];
    }

    /**
     * IPv4/IPv6 ranges that must never be reachable through URL import.
     *
     * FILTER_FLAG_NO_PRIV_RANGE|NO_RES_RANGE alone leaves gaps (notably CGNAT
     * 100.64.0.0/10 and the cloud metadata endpoint), so the ranges are listed
     * explicitly here as well.
     *
     * @var array<int, string>
     */
    protected array $blockedCidrs = [
        // IPv4
        '0.0.0.0/8',          // "this network" / unspecified
        '10.0.0.0/8',         // RFC1918
        '100.64.0.0/10',      // CGNAT (RFC6598)
        '127.0.0.0/8',        // loopback
        '169.254.0.0/16',     // link-local, includes 169.254.169.254 metadata
        '172.16.0.0/12',      // RFC1918
        '192.0.0.0/24',       // IETF protocol assignments
        '192.0.2.0/24',       // TEST-NET-1
        '192.168.0.0/16',     // RFC1918
        '198.18.0.0/15',      // benchmarking
        '198.51.100.0/24',    // TEST-NET-2
        '203.0.113.0/24',     // TEST-NET-3
        '224.0.0.0/4',        // multicast
        '240.0.0.0/4',        // reserved
        '255.255.255.255/32', // broadcast
        // IPv6
        '::/128',             // unspecified
        '::1/128',            // loopback
        '64:ff9b::/96',       // NAT64
        '100::/64',           // discard-only
        '2001:db8::/32',      // documentation
        'fc00::/7',           // unique-local
        'fe80::/10',          // link-local
        'ff00::/8',           // multicast
    ];

    /**
     * Decide whether an IP falls inside a blocked range.
     */
    protected function isBlockedIp(string $ip): bool
    {
        if (filter_var($ip, FILTER_VALIDATE_IP) === false) {
            return true; // not an address we can reason about → refuse
        }

        // Unwrap IPv4-mapped/compatible IPv6 (::ffff:169.254.169.254) so the
        // IPv4 rules below still apply.
        if (preg_match('/^::(?:ffff:)?(\d{1,3}(?:\.\d{1,3}){3})$/i', $ip, $m)
            && filter_var($m[1], FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) !== false) {
            $ip = $m[1];
        }

        foreach ($this->blockedCidrs as $cidr) {
            if ($this->ipInCidr($ip, $cidr)) {
                return true;
            }
        }

        // Keep PHP's own private/reserved detection as a backstop for anything
        // the explicit list misses.
        return filter_var(
            $ip,
            FILTER_VALIDATE_IP,
            FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE
        ) === false;
    }

    /**
     * Binary CIDR containment test, valid for both IPv4 and IPv6.
     */
    protected function ipInCidr(string $ip, string $cidr): bool
    {
        [$subnet, $bits] = array_pad(explode('/', $cidr, 2), 2, null);
        $bits = (int) $bits;

        $ipBin = @inet_pton($ip);
        $subnetBin = @inet_pton($subnet);

        if ($ipBin === false || $subnetBin === false) {
            return false;
        }

        // Different families never match.
        if (strlen($ipBin) !== strlen($subnetBin)) {
            return false;
        }

        $wholeBytes = intdiv($bits, 8);
        $remainder = $bits % 8;

        if ($wholeBytes > 0
            && strncmp($ipBin, $subnetBin, $wholeBytes) !== 0) {
            return false;
        }

        if ($remainder === 0) {
            return true;
        }

        $mask = ~((1 << (8 - $remainder)) - 1) & 0xFF;

        return (ord($ipBin[$wholeBytes]) & $mask) === (ord($subnetBin[$wholeBytes]) & $mask);
    }

    /**
     * Derive a reasonable original filename from a URL + extension.
     */
    protected function filenameFromUrl(string $url, string $extension): string
    {
        $path = parse_url($url, PHP_URL_PATH) ?? '';
        $base = pathinfo($path, PATHINFO_FILENAME) ?: 'download';
        $base = preg_replace('/[^A-Za-z0-9_\-]/', '-', $base);
        $base = trim($base, '-') ?: 'download';

        return $base . '.' . $extension;
    }

    /**
     * Serialize a Media model for JSON consumers (grid/modal/picker).
     *
     * @return array<string, mixed>
     */
    protected function toArray(Media $media): array
    {
        return [
            'id'                => $media->id,
            'url'               => $media->url,
            'thumbnail_url'     => $media->thumbnail_url,
            'filename'          => $media->filename,
            'original_filename' => $media->original_filename,
            'mime_type'         => $media->mime_type,
            'type'              => $media->type,
            'size'              => $media->size,
            'width'             => $media->width,
            'height'            => $media->height,
            'directory'         => $media->directory,
            'alt'               => $media->alt,
            'title'             => $media->title,
            'caption'           => $media->caption,
            'description'       => $media->description,
            'uploaded_by'       => $media->user?->name,
            'created_at'        => optional($media->created_at)->toDateTimeString(),
            'update_url'        => route('admin.media.update', $media),
            'destroy_url'       => route('admin.media.destroy', $media),
        ];
    }

    /**
     * Get all unique folders from media.
     *
     * @return array<int, string>
     */
    protected function getFolders(): array
    {
        return array_values(
            Media::whereNotNull('directory')
                ->select('directory')
                ->distinct()
                ->pluck('directory')
                ->toArray()
        );
    }

    /**
     * Determine the type of file based on MIME type.
     */
    protected function getFileType(string $mimeType): string
    {
        if (str_starts_with($mimeType, 'image/')) {
            return 'image';
        }
        if (str_starts_with($mimeType, 'application/pdf') || str_contains($mimeType, 'document')) {
            return 'document';
        }
        if (str_starts_with($mimeType, 'video/')) {
            return 'video';
        }

        return 'document';
    }

    /**
     * Generate a max 300x300 thumbnail for an image using GD.
     */
    protected function generateThumbnail(string $filePath, string $folder, string $filename): void
    {
        try {
            $thumbnailDir = storage_path('app/public/' . $folder . '/thumbnails');
            if (! file_exists($thumbnailDir)) {
                mkdir($thumbnailDir, 0755, true);
            }

            $thumbnailPath = $thumbnailDir . '/' . $filename;
            $imageInfo = @getimagesize($filePath);
            if (! $imageInfo) {
                return;
            }
            $mimeType = $imageInfo['mime'];

            switch ($mimeType) {
                case 'image/jpeg':
                    $image = imagecreatefromjpeg($filePath);
                    break;
                case 'image/png':
                    $image = imagecreatefrompng($filePath);
                    break;
                case 'image/gif':
                    $image = imagecreatefromgif($filePath);
                    break;
                case 'image/webp':
                    $image = imagecreatefromwebp($filePath);
                    break;
                default:
                    return; // Unsupported (e.g. SVG) — no raster thumbnail
            }

            if (! $image) {
                return;
            }

            $maxWidth = 300;
            $maxHeight = 300;
            $width = imagesx($image);
            $height = imagesy($image);
            $ratio = min($maxWidth / $width, $maxHeight / $height, 1);
            $newWidth = (int) ($width * $ratio);
            $newHeight = (int) ($height * $ratio);

            $thumbnail = imagecreatetruecolor($newWidth, $newHeight);
            if ($mimeType === 'image/png' || $mimeType === 'image/gif') {
                imagealphablending($thumbnail, false);
                imagesavealpha($thumbnail, true);
            }
            imagecopyresampled($thumbnail, $image, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);

            switch ($mimeType) {
                case 'image/jpeg':
                    imagejpeg($thumbnail, $thumbnailPath, 85);
                    break;
                case 'image/png':
                    imagepng($thumbnail, $thumbnailPath, 8);
                    break;
                case 'image/gif':
                    imagegif($thumbnail, $thumbnailPath);
                    break;
                case 'image/webp':
                    imagewebp($thumbnail, $thumbnailPath, 85);
                    break;
            }

            imagedestroy($image);
            imagedestroy($thumbnail);
        } catch (\Throwable $e) {
            // Thumbnail generation is best-effort; ignore failures.
        }
    }
}
