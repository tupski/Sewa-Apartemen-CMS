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
        'show_on_frontend',
        'custom_name',
        'distance_m',
        'walking_distance_m',
        'walking_duration_s',
        'driving_distance_m',
        'driving_duration_s',
        'motorcycle_distance_m',
        'motorcycle_duration_s',
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
        'driving_distance_m' => 'integer',
        'driving_duration_s' => 'integer',
        'motorcycle_distance_m' => 'integer',
        'motorcycle_duration_s' => 'integer',
        'show_on_frontend' => 'boolean',
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
     * The presentation name: admin custom name wins over the provider name.
     */
    public function getDisplayNameAttribute(): string
    {
        $custom = trim((string) $this->custom_name);

        if ($custom !== '') {
            return $custom;
        }

        return (string) ($this->place->name ?? '');
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
        return self::durationMinutes($this->walking_duration_s);
    }

    /**
     * Human-readable walking time ("8 min walk"), or null when unmeasured.
     */
    public function getWalkingDurationFormattedAttribute(): ?string
    {
        return self::formatDuration($this->walking_duration_s, 'min walk');
    }

    /**
     * Human-readable driving distance, or null when unmeasured.
     */
    public function getDrivingDistanceFormattedAttribute(): ?string
    {
        return self::formatMetres($this->driving_distance_m);
    }

    /**
     * Human-readable driving time ("12 min drive"), or null when unmeasured.
     */
    public function getDrivingDurationFormattedAttribute(): ?string
    {
        return self::formatDuration($this->driving_duration_s, 'min drive');
    }

    /**
     * Human-readable motorcycle distance, or null when unmeasured.
     */
    public function getMotorcycleDistanceFormattedAttribute(): ?string
    {
        return self::formatMetres($this->motorcycle_distance_m);
    }

    /**
     * Human-readable motorcycle time ("9 min ride"), or null when unmeasured.
     */
    public function getMotorcycleDurationFormattedAttribute(): ?string
    {
        return self::formatDuration($this->motorcycle_duration_s, 'min ride');
    }

    /**
     * Duration seconds → whole minutes (rounded up), null-safe.
     */
    private static function durationMinutes(?int $seconds): ?int
    {
        if ($seconds === null) {
            return null;
        }

        return (int) max(1, (int) ceil($seconds / 60));
    }

    /**
     * Format seconds as "<n> <unit>" using the localized "min" fragment.
     */
    private static function formatDuration(?int $seconds, string $suffix): ?string
    {
        $minutes = self::durationMinutes($seconds);

        return $minutes === null ? null : $minutes.' '.__($suffix);
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
