<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PropertyPhoto extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'property_id',
        'media_id',
        'category',
        'sort_order',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'sort_order' => 'integer',
    ];

    /**
     * Get the property that owns the photo.
     */
    public function property()
    {
        return $this->belongsTo(Property::class);
    }

    /**
     * Get the media record for the photo.
     */
    public function media()
    {
        return $this->belongsTo(Media::class);
    }
}
