<?php

namespace App\Services;

use App\Models\Media;
use Illuminate\Support\Facades\Storage;

/**
 * Generates responsive image variants for Media records.
 *
 * For every raster image upload the service produces:
 *   - width variants: 400 / 800 / 1600 px (never upscaling past the original)
 *   - format variants: WebP and AVIF re-encodes (when the source is not
 *     already that format), following the same width ladder
 *
 * Everything is written next to the original under
 * `variants/` and recorded in `media.metadata.variants`. Generation is
 * best-effort: when the GD extension lacks a codec (e.g. AVIF), that variant
 * is simply skipped — uploads never fail because of variant generation.
 *
 * Backed by the `media:generate-variants` command for backfilling legacy
 * media, and consumed by the `x-media-image` Blade component which renders
 * `<picture>` + `srcset`/`sizes` markup.
 */
class ImageVariantService
{
    /**
     * Width ladder for responsive variants (px).
     *
     * @var array<int, int>
     */
    public const WIDTHS = [400, 800, 1600];

    /**
     * Modern formats generated alongside the original (mime => extension).
     *
     * @var array<string, string>
     */
    public const MODERN_FORMATS = [
        'image/webp' => 'webp',
        'image/avif' => 'avif',
    ];

    /**
     * JPEG/WebP/AVIF encoding quality.
     */
    protected int $quality = 82;

    /**
     * Generate all variants for a Media record.
     *
     * @param  bool  $force  regenerate even when variants already exist
     * @return array<string, array<int, array<string, int|string>>> variants map
     *                                                              written to media.metadata, e.g.:
     *                                                              [
     *                                                              'original' => [['width' => 1600, 'url' => ...], ...],
     *                                                              'webp'     => [['width' => 800, 'url' => ...], ...],
     *                                                              'avif'     => [...],
     *                                                              ]
     */
    public function generateFor(Media $media, bool $force = false): array
    {
        if (! $this->isRasterImage($media)) {
            return [];
        }

        $existing = $media->metadata['variants'] ?? null;
        if (is_array($existing) && $existing !== [] && ! $force) {
            return $existing;
        }

        $disk = Storage::disk($media->disk);
        $sourcePath = $media->directory.'/'.$media->filename;

        if (! $disk->exists($sourcePath)) {
            return [];
        }

        $sourceData = $disk->get($sourcePath);
        $source = @imagecreatefromstring($sourceData);
        if ($source === false) {
            return [];
        }

        $originalWidth = imagesx($source);
        $originalHeight = imagesy($source);

        $variants = [
            'original' => $this->collectOriginalEntries($media, $originalWidth, $originalHeight),
        ];

        // Width variants of the original format.
        foreach ($this->targetWidths($originalWidth) as $width) {
            $entry = $this->writeResized($disk, $media, $source, $originalWidth, $originalHeight, $width, null);
            if ($entry !== null) {
                $variants['original'][] = $entry;
            }
        }

        // Modern-format variants (WebP / AVIF), following the same ladder.
        foreach (self::MODERN_FORMATS as $mime => $extension) {
            if ($media->mime_type === $mime) {
                continue; // The original is already this format.
            }
            if (! $this->codecSupported($mime)) {
                continue;
            }

            $formatVariants = [];
            foreach ($this->targetWidths($originalWidth) as $width) {
                $entry = $this->writeResized($disk, $media, $source, $originalWidth, $originalHeight, $width, $mime);
                if ($entry !== null) {
                    $formatVariants[] = $entry;
                }
            }

            if ($formatVariants !== []) {
                $variants[$extension] = $formatVariants;
            }
        }

        imagedestroy($source);

        $media->forceFill([
            'metadata' => array_merge($media->metadata ?? [], ['variants' => $variants]),
        ])->saveQuietly();

        return $variants;
    }

    /**
     * Remove every generated variant file for a Media record (original stays).
     */
    public function deleteFor(Media $media): void
    {
        $variants = $media->metadata['variants'] ?? null;
        if (! is_array($variants)) {
            return;
        }

        $disk = Storage::disk($media->disk);

        foreach ($variants as $entries) {
            if (! is_array($entries)) {
                continue;
            }
            foreach ($entries as $entry) {
                $path = $entry['path'] ?? null;
                if (is_string($path) && $disk->exists($path)) {
                    $disk->delete($path);
                }
            }
        }
    }

    // ─────────────────────────────────────────────────────────────────────

    /**
     * Widths to generate for a given original: the full ladder, capped so we
     * never upscale past the original width.
     *
     * @return array<int, int>
     */
    protected function targetWidths(int $originalWidth): array
    {
        $widths = array_values(array_filter(
            self::WIDTHS,
            fn (int $w) => $w < $originalWidth
        ));

        // Very small originals get no width variants (browser scales fine).
        return $widths;
    }

    /**
     * Resize + re-encode the source to a target width (and optional format),
     * then write it to disk. Returns the metadata entry, or null on failure.
     *
     * @return array{width: int, height: int, size: int, url: string, path: string}|null
     */
    protected function writeResized($disk, Media $media, $source, int $originalWidth, int $originalHeight, int $targetWidth, ?string $targetMime)
    {
        try {
            $scale = $targetWidth / $originalWidth;
            $targetHeight = max(1, (int) round($originalHeight * $scale));

            $resized = imagecreatetruecolor($targetWidth, $targetHeight);

            // Preserve transparency for PNG/GIF/WebP/AVIF targets.
            imagealphablending($resized, false);
            imagesavealpha($resized, true);

            imagecopyresampled($resized, $source, 0, 0, 0, 0, $targetWidth, $targetHeight, $originalWidth, $originalHeight);

            $extension = $targetMime !== null
                ? self::MODERN_FORMATS[$targetMime]
                : ($media->extension ?: 'jpg');

            $filename = pathinfo($media->filename, PATHINFO_FILENAME);
            $variantPath = $media->directory.'/variants/'.$filename.'-'.$targetWidth.'w.'.$extension;

            $tmp = tmpfile();
            $tmpPath = stream_get_meta_data($tmp)['uri'];

            $encoded = match (true) {
                $targetMime === 'image/avif' => imageavif($resized, $tmpPath, $this->quality),
                $targetMime === 'image/webp' => imagewebp($resized, $tmpPath, $this->quality),
                $media->mime_type === 'image/png' => imagepng($resized, $tmpPath, 8),
                $media->mime_type === 'image/gif' => imagegif($resized, $tmpPath),
                default => imagejpeg($resized, $tmpPath, $this->quality),
            };

            if ($encoded !== true) {
                imagedestroy($resized);
                fclose($tmp);

                return null;
            }

            $bytes = file_get_contents($tmpPath) ?: '';
            $size = strlen($bytes);
            $disk->put($variantPath, $bytes);
            fclose($tmp);
            imagedestroy($resized);

            return [
                'width' => $targetWidth,
                'height' => $targetHeight,
                'size' => $size,
                'url' => $disk->url($variantPath),
                'path' => $variantPath,
            ];
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * Metadata entries describing the untouched original.
     *
     * @return array<int, array<string, int|string>>
     */
    protected function collectOriginalEntries(Media $media, int $originalWidth, int $originalHeight): array
    {
        $disk = Storage::disk($media->disk);
        $originalPath = $media->directory.'/'.$media->filename;

        return [
            [
                'width' => $originalWidth,
                'height' => $originalHeight,
                'size' => (int) $media->size,
                'url' => $disk->url($originalPath),
                'path' => $originalPath,
            ],
        ];
    }

    /**
     * Whether GD can decode/encode the given mime type.
     */
    protected function codecSupported(string $mime): bool
    {
        return match ($mime) {
            'image/webp' => function_exists('imagewebp'),
            'image/avif' => function_exists('imageavif'),
            default => true,
        };
    }

    /**
     * Whether the media record is a raster image we can re-encode with GD.
     */
    protected function isRasterImage(Media $media): bool
    {
        if ($media->type !== 'image') {
            return false;
        }

        // SVG is vector — no raster variants.
        return $media->mime_type !== 'image/svg+xml';
    }
}
