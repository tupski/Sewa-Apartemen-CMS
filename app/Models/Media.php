<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class Media extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'disk',
        'directory',
        'filename',
        'original_filename',
        'mime_type',
        'extension',
        'size',
        'width',
        'height',
        'type',
        'alt',
        'title',
        'caption',
        'description',
        'metadata',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'size' => 'integer',
        'width' => 'integer',
        'height' => 'integer',
        'metadata' => 'json',
    ];

    /**
     * Get the user that uploaded the media.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the full URL for the media file.
     */
    public function getUrlAttribute(): string
    {
        return Storage::disk($this->disk)->url($this->directory . '/' . $this->filename);
    }

    /**
     * Get the thumbnail URL for the media file.
     */
    public function getThumbnailUrlAttribute(): string
    {
        $thumbnailPath = $this->directory . '/thumbnails/' . $this->filename;

        if (Storage::disk($this->disk)->exists($thumbnailPath)) {
            return Storage::disk($this->disk)->url($thumbnailPath);
        }

        return $this->url;
    }

    /**
     * Delete the media file from storage.
     */
    public function deleteFile(): void
    {
        if (Storage::disk($this->disk)->exists($this->directory . '/' . $this->filename)) {
            Storage::disk($this->disk)->delete($this->directory . '/' . $this->filename);
        }

        // Delete thumbnail if exists
        $thumbnailPath = $this->directory . '/thumbnails/' . $this->filename;
        if (Storage::disk($this->disk)->exists($thumbnailPath)) {
            Storage::disk($this->disk)->delete($thumbnailPath);
        }
    }
}
