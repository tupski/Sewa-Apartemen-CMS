<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Place extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'geoapify_place_id',
        'name',
        'category',
        'lat',
        'lng',
        'address',
        'website',
        'phone',
        'raw_category',
        'fetched_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'lat' => 'float',
        'lng' => 'float',
        'fetched_at' => 'datetime',
    ];

    /**
     * Get the property_places pivot records for this place.
     */
    public function propertyPlaces()
    {
        return $this->hasMany(PropertyPlace::class);
    }

    /**
     * The Artivo-managed category row matching this place's category slug.
     *
     * Matches the exact slug first; a raw child key (`public_transport.train`)
     * resolves to its parent row (`public_transport`) as a fallback.
     */
    public function placeCategory()
    {
        return $this->belongsTo(PlaceCategory::class, 'category', 'slug')
            ->orWhere('slug', strtok((string) $this->category, '.'));
    }

    /**
     * Localized category display label (DB-driven, never the raw provider key).
     */
    public function categoryLabel(): string
    {
        return PlaceCategory::labelForSlug($this->category);
    }
}
