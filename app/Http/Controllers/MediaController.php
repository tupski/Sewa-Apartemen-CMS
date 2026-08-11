<?php

namespace App\Http\Controllers;

use App\Models\Media;
use App\Http\Requests\MediaRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\Facades\Image;

class MediaController extends Controller
{
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

        // Filter by directory
        if ($request->has('folder')) {
            $query->where('directory', $request->folder);
        }

        // Filter by type
        if ($request->has('type') && $request->type) {
            $query->where('type', $request->type);
        }

        // Search
        if ($request->has('search') && $request->search) {
            $query->where(function($q) use ($request) {
                $q->where('filename', 'like', '%' . $request->search . '%')
                  ->orWhere('original_filename', 'like', '%' . $request->search . '%')
                  ->orWhere('alt', 'like', '%' . $request->search . '%');
            });
        }

        $media = $query->orderBy('created_at', 'desc')->paginate(20);
        $folders = $this->getFolders();

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
     * Store a newly created media file.
     */
    public function store(MediaRequest $request)
    {
        try {
            $validated = $request->validated();
            $file = $request->file('file');
            $folder = $validated['folder'] ?? 'media/' . date('Y/m');
            $type = $this->getFileType($file->getMimeType());

            // Generate safe filename
            $originalName = $file->getClientOriginalName();
            $safeName = Str::slug(pathinfo($originalName, PATHINFO_FILENAME));
            $extension = $file->getClientOriginalExtension();
            $filename = $safeName . '-' . time() . '-' . Str::random(10) . '.' . $extension;

            // Store file
            $path = $file->storeAs($folder, $filename, 'public');

            // Get image dimensions if image
            $width = null;
            $height = null;
            if ($type === 'image') {
                $imageInfo = getimagesize($file->getRealPath());
                if ($imageInfo) {
                    $width = $imageInfo[0];
                    $height = $imageInfo[1];
                }

                // Generate thumbnail
                $this->generateThumbnail($file->getRealPath(), $folder, $filename);
            }

            // Create media record
            Media::create([
                'user_id' => auth()->id(),
                'disk' => 'public',
                'directory' => $folder,
                'filename' => $filename,
                'original_filename' => $originalName,
                'mime_type' => $file->getMimeType(),
                'extension' => $extension,
                'size' => $file->getSize(),
                'width' => $width,
                'height' => $height,
                'type' => $type,
                'alt' => $validated['alt'] ?? null,
                'title' => $validated['title'] ?? null,
                'caption' => $validated['caption'] ?? null,
                'description' => $validated['description'] ?? null,
            ]);

            return back()->with('success', 'Media file uploaded successfully.');
        } catch (\Exception $e) {
            return back()
                ->withInput()
                ->with('error', 'Failed to upload media: ' . $e->getMessage());
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
     * Update the specified media file.
     */
    public function update(Request $request, Media $media)
    {
        try {
            $validated = $request->validate([
                'alt' => 'nullable|string|max:255',
                'title' => 'nullable|string|max:255',
                'caption' => 'nullable|string|max:500',
                'description' => 'nullable|string',
            ]);

            $media->update([
                'alt' => $validated['alt'] ?? $media->alt,
                'title' => $validated['title'] ?? $media->title,
                'caption' => $validated['caption'] ?? $media->caption,
                'description' => $validated['description'] ?? $media->description,
            ]);

            return back()->with('success', 'Media file updated successfully.');
        } catch (\Exception $e) {
            return back()
                ->withInput()
                ->with('error', 'Failed to update media: ' . $e->getMessage());
        }
    }

    /**
     * Remove the specified media file.
     */
    public function destroy(Media $media)
    {
        try {
            $media->deleteFile();
            $media->delete();

            return redirect()->route('admin.media.index')
                ->with('success', 'Media file deleted successfully.');
        } catch (\Exception $e) {
            return back()
                ->with('error', 'Failed to delete media: ' . $e->getMessage());
        }
    }

    /**
     * Get all unique folders from media.
     */
    protected function getFolders(): array
    {
        $folders = Media::whereNotNull('directory')
            ->select('directory')
            ->distinct()
            ->pluck('directory')
            ->toArray();

        return array_values($folders);
    }

    /**
     * Determine the type of file based on MIME type.
     */
    protected function getFileType(string $mimeType): string
    {
        if (str_starts_with($mimeType, 'image/')) {
            return 'image';
        } elseif (str_starts_with($mimeType, 'application/pdf') || str_contains($mimeType, 'document')) {
            return 'document';
        } elseif (str_starts_with($mimeType, 'video/')) {
            return 'video';
        }

        return 'document';
    }

    /**
     * Generate thumbnail for image.
     */
    protected function generateThumbnail(string $filePath, string $folder, string $filename): void
    {
        try {
            $thumbnailDir = storage_path('app/public/' . $folder . '/thumbnails');

            // Create thumbnail directory if it doesn't exist
            if (!file_exists($thumbnailDir)) {
                mkdir($thumbnailDir, 0755, true);
            }

            // Generate thumbnail using GD library (basic implementation)
            $thumbnailPath = $thumbnailDir . '/' . $filename;

            $imageInfo = getimagesize($filePath);
            $mimeType = $imageInfo['mime'];

            // Create image resource based on type
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
                    return; // Unsupported format
            }

            if (!$image) {
                return;
            }

            // Calculate thumbnail dimensions (max 300x300)
            $maxWidth = 300;
            $maxHeight = 300;
            $width = imagesx($image);
            $height = imagesy($image);

            $ratio = min($maxWidth / $width, $maxHeight / $height);
            $newWidth = (int)($width * $ratio);
            $newHeight = (int)($height * $ratio);

            // Create thumbnail
            $thumbnail = imagecreatetruecolor($newWidth, $newHeight);

            // Preserve transparency for PNG and GIF
            if ($mimeType === 'image/png' || $mimeType === 'image/gif') {
                imagealphablending($thumbnail, false);
                imagesavealpha($thumbnail, true);
            }

            imagecopyresampled($thumbnail, $image, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);

            // Save thumbnail based on original type
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

            // Free memory
            imagedestroy($image);
            imagedestroy($thumbnail);
        } catch (\Exception $e) {
            // Thumbnail generation failed, but continue
        }
    }
}
