<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PropertyPlace extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'property_id',
        'place_id',
        'source',
        'distance_m',
        'sort_order',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'distance_m' => 'integer',
    ];

    /**
     * Get the property that this pivot record belongs to.
     */
    public function property()
    {
        return $this->belongsTo(Property::class);
    }

    /**
     * Get the place that this pivot record belongs to.
     */
    public function place()
    {
        return $this->belongsTo(Place::class);
    }

    /**
     * Human-readable formatted distance string.
     *
     * Returns "850m" for distances under 1 km, or "1.2km" for distances >= 1 km.
     */
    public function getDistanceFormattedAttribute(): ?string
    {
        if ($this->distance_m === null) {
            return null;
        }

        if ($this->distance_m < 1000) {
            return $this->distance_m.'m';
        }

        return round($this->distance_m / 1000, 1).'km';
    }
}
