<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class Property extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'slug',
        'description',
        'unit_types',
        'weekend_days',
        'prices',
        'photo_categories',
        'address',
        'city',
        'province',
        'postal_code',
        'latitude',
        'longitude',
        'featured_image_id',
        'status',
        'meta_title',
        'meta_description',
        'is_featured',
        'order',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'is_featured' => 'boolean',
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8',
        'order' => 'integer',
        'unit_types' => 'array',
        'weekend_days' => 'array',
        'prices' => 'array',
        'photo_categories' => 'array',
    ];

    /** Available room types (key => label). */
    public const UNIT_TYPES = [
        'studio' => 'Studio',
        '1br' => '1 BR',
        '2br' => '2 BR',
        '3br' => '3 BR',
        '4br' => '4 BR',
        'penthouse' => 'Penthouse',
    ];

    /** Default photo gallery categories. */
    public const DEFAULT_PHOTO_CATEGORIES = [
        'Lobby',
        'Lift',
        'Bedroom',
        'Toilet',
        'Swimming Pool',
        'Playground',
        'View',
    ];

    /**
     * Photo gallery categories for this property (defaults + custom).
     */
    public function photoCategories(): array
    {
        $categories = $this->photo_categories ?? [];

        return array_values(array_unique(array_filter((array) $categories))) ?: self::DEFAULT_PHOTO_CATEGORIES;
    }

    /**
     * Gallery photos.
     */
    public function photos()
    {
        return $this->hasMany(PropertyPhoto::class)->with('media')->orderBy('sort_order')->orderBy('id');
    }

    /**
     * Get the amenities for the property.
     */
    public function amenities()
    {
        return $this->belongsToMany(Amenity::class, 'amenity_property')
                    ->withTimestamps();
    }

    /**
     * Get the featured image for the property.
     */
    public function featuredImage()
    {
        return $this->belongsTo(Media::class, 'featured_image_id');
    }

    /**
     * Human label for a room type key.
     */
    public function typeLabel(string $type): string
    {
        return self::UNIT_TYPES[$type] ?? ucfirst(str_replace('-', ' ', $type));
    }

    /**
     * Whether this property offers the given room type.
     */
    public function hasType(string $type): bool
    {
        return in_array($type, $this->unit_types ?? [], true);
    }

    /**
     * Days of the week treated as weekend (0=Sun..6=Sat). Default: Sat + Sun.
     */
    public function weekendDays(): array
    {
        return $this->weekend_days ?? [6, 0];
    }

    /**
     * Whether the given weekday (0=Sun..6=Sat) counts as weekend for this property.
     */
    public function isWeekendDay(int $day): bool
    {
        return in_array($day, $this->weekendDays(), true);
    }

    /**
     * Get one price entry for a room type (e.g. 'night_wd', 't6_we', 'weekly').
     */
    public function priceFor(string $type, string $key): ?float
    {
        $value = $this->prices[$type][$key] ?? null;

        return $value === null || $value === '' ? null : (float) $value;
    }

    /**
     * Cheapest weekday nightly rate across all room types (for "Mulai dari").
     */
    public function cheapestNight(): ?float
    {
        $rates = [];

        foreach ($this->unit_types ?? [] as $type) {
            if ($rate = $this->priceFor($type, 'night_wd')) {
                $rates[] = $rate;
            }
        }

        return $rates ? min($rates) : null;
    }

    /**
     * Scope a query to only include published properties.
     */
    public function scopePublished($query)
    {
        return $query->where('status', 'published');
    }

    /**
     * Scope a query to only include draft properties.
     */
    public function scopeDraft($query)
    {
        return $query->where('status', 'draft');
    }

    /**
     * Scope a query to only include featured properties.
     */
    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
    }

    /**
     * Get the SEO metadata for the property.
     */
    public function seo()
    {
        return $this->morphOne(SeoMetadata::class, 'seoable');
    }

    /**
     * The "booted" method of the model.
     */
    protected static function booted(): void
    {
        static::saved(function () {
            \Illuminate\Support\Facades\Cache::forget('sitemap.xml');
            \Illuminate\Support\Facades\Cache::forget('dashboard_stats');
        });

        static::deleted(function () {
            \Illuminate\Support\Facades\Cache::forget('sitemap.xml');
            \Illuminate\Support\Facades\Cache::forget('dashboard_stats');
        });
    }
}
