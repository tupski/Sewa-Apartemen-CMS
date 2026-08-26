<?php

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;

if (! function_exists('upload_file')) {
    /**
     * Upload a file to the public disk with a standardized naming convention:
     *
     * Folder:  {baseFolder}/{subFolder1}/{subFolder2}/...
     * Filename: {prefix}_{category}_{DDMMYYYY}_{random8}.{ext}
     *
     * Example:
     *   upload_file($file, [
     *       'base_folder'   => 'Apartment',
     *       'sub_folders'   => ['Skyhouse BSD', 'Bedroom'],
     *       'name_prefix'   => 'Skyhouse BSD',
     *       'name_category' => 'Bedroom',
     *   ]);
     *   → Apartment/Skyhouse-BSD/Bedroom/Skyhouse-BSD_Bedroom_26082026_aBcDeFgH.jpg
     *
     * @param  UploadedFile  $file   The uploaded file instance.
     * @param  array  $options
     *   - base_folder   (string)  Root folder name.              Default: 'Apartment'
     *   - sub_folders   (array)   Sub-folder names (slugified).  Default: []
     *   - name_prefix   (string)  Prefix before the category.    Default: 'Skyhouse'
     *   - name_category (string)  Category after the prefix.     Default: 'media'
     *   - extension     (string|null) Force a specific extension. Default: auto-detected from MIME.
     * @return array{path: string, filename: string, folder: string, extension: string}
     */
    function upload_file(UploadedFile $file, array $options = []): array
    {
        $baseFolder   = $options['base_folder'] ?? 'Apartment';
        $subFolders   = (array) ($options['sub_folders'] ?? []);
        $namePrefix   = $options['name_prefix'] ?? 'Skyhouse';
        $nameCategory = $options['name_category'] ?? 'media';
        $forcedExt    = $options['extension'] ?? null;

        // ── Build folder path ──────────────────────────────────────────────
        $folderParts = [Str::slug($baseFolder)];
        foreach ($subFolders as $sf) {
            $sf = trim((string) $sf);
            if ($sf !== '') {
                $folderParts[] = Str::slug($sf);
            }
        }
        $folder = implode('/', $folderParts);

        // ── Determine extension (MIME-based for security) ──────────────────
        $mimeMap = [
            'image/jpeg'      => 'jpg',
            'image/png'       => 'png',
            'image/gif'       => 'gif',
            'image/webp'      => 'webp',
            'image/svg+xml'   => 'svg',
            'application/pdf' => 'pdf',
            'application/msword' => 'doc',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => 'docx',
            'video/mp4'       => 'mp4',
            'video/avi'       => 'avi',
            'video/quicktime' => 'mov',
        ];

        $extension = $forcedExt
            ?? $mimeMap[$file->getMimeType()]
            ?? strtolower($file->getClientOriginalExtension())
            ?? 'bin';

        // ── Build filename ─────────────────────────────────────────────────
        $datePart  = now()->format('dmY');
        $random    = Str::random(8);
        $slugPrefix   = Str::slug($namePrefix);
        $slugCategory = Str::slug($nameCategory);

        $filename = sprintf(
            '%s_%s_%s_%s.%s',
            $slugPrefix ?: 'file',
            $slugCategory ?: 'media',
            $datePart,
            $random,
            $extension
        );

        // ── Store file ─────────────────────────────────────────────────────
        $path = $file->storeAs($folder, $filename, 'public');

        return [
            'path'      => $path,
            'filename'  => $filename,
            'folder'    => $folder,
            'extension' => $extension,
        ];
    }
}

if (! function_exists('mime_to_extension')) {
    /**
     * Convert a MIME type to a safe file extension.
     *
     * @param  string  $mimeType
     * @return string|null
     */
    function mime_to_extension(string $mimeType): ?string
    {
        $map = [
            'image/jpeg'      => 'jpg',
            'image/png'       => 'png',
            'image/gif'       => 'gif',
            'image/webp'      => 'webp',
            'image/svg+xml'   => 'svg',
            'application/pdf' => 'pdf',
            'application/msword' => 'doc',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => 'docx',
            'video/mp4'       => 'mp4',
            'video/avi'       => 'avi',
            'video/quicktime' => 'mov',
        ];

        return $map[$mimeType] ?? null;
    }
}
