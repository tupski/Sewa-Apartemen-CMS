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
        'walking_distance_m',
        'walking_duration_s',
        'sort_order',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'distance_m' => 'integer',
        'walking_distance_m' => 'integer',
        'walking_duration_s' => 'integer',
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
     * Human-readable distance string.
     *
     * Prefers the measured walking route distance and falls back to the
     * straight-line distance for rows synced before walking metrics existed (or
     * for manual rows). Returns "850m" / "1.2km", or null when neither is known.
     */
    public function getDistanceFormattedAttribute(): ?string
    {
        return self::formatMetres($this->walking_distance_m ?? $this->distance_m);
    }

    /**
     * Human-readable walking-route distance ("650 m"), or null when unmeasured.
     */
    public function getWalkingDistanceFormattedAttribute(): ?string
    {
        return self::formatMetres($this->walking_distance_m);
    }

    /**
     * Walking duration in whole minutes, rounded up so "9 min" never understates
     * a 8m20s walk. Null when no walking measurement is stored.
     */
    public function getWalkingMinutesAttribute(): ?int
    {
        if ($this->walking_duration_s === null) {
            return null;
        }

        return (int) max(1, (int) ceil($this->walking_duration_s / 60));
    }

    /**
     * Human-readable walking time ("8 min walk"), or null when unmeasured.
     */
    public function getWalkingDurationFormattedAttribute(): ?string
    {
        $minutes = $this->walking_minutes;

        return $minutes === null ? null : $minutes.' '.__('min walk');
    }

    /**
     * Format a metre value as "850m" (< 1 km) or "1.2km".
     */
    private static function formatMetres(?int $metres): ?string
    {
        if ($metres === null) {
            return null;
        }

        if ($metres < 1000) {
            return $metres.'m';
        }

        return round($metres / 1000, 1).'km';
    }
}
