<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Booking;

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
        'max_days',
        'checkin_time',
        'checkout_time',
        'checkin_method',
        'required_documents',
        'nearby_places',
        'max_guests',
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
        'max_days' => 'integer',
        'max_guests' => 'integer',
        'unit_types' => 'array',
        'weekend_days' => 'array',
        'prices' => 'array',
        'photo_categories' => 'array',
        'required_documents' => 'array',
        'nearby_places' => 'array',
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
     * Get all bookings for this property.
     */
    public function bookings()
    {
        return $this->hasMany(Booking::class);
    }

    /**
     * Promo rates defined for this property.
     */
    public function promoRates()
    {
        return $this->hasMany(PromoRate::class);
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
     * Cheapest weekday nightly rate across all room types (for "Mulai dari" — daily only).
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
     * Absolute lowest price across all room types and all booking types
     * (transit slots, daily, weekly, monthly). Returns null if no prices set.
     */
    public function lowestPrice(): ?float
    {
        $rates = [];
        $allKeys = ['night_wd', 'night_we', 'weekly', 'monthly',
                    't3_wd', 't3_we', 't6_wd', 't6_we',
                    't9_wd', 't9_we', 't12_wd', 't12_we',
                    't24_wd', 't24_we'];

        foreach ($this->unit_types ?? [] as $type) {
            foreach ($allKeys as $key) {
                $v = $this->priceFor($type, $key);
                if ($v !== null && $v > 0) {
                    $rates[] = $v;
                }
            }
        }

        return $rates ? min($rates) : null;
    }

    /**
     * Whether the booking method key ("transit"|"weekly"|"monthly"|"daily")
     * is available for this property (i.e. any room type has a price set).
     */
    public function hasBookingType(string $key): bool
    {
        $priceKeys = [
            'transit' => ['t3_wd', 't3_we', 't6_wd', 't6_we', 't9_wd', 't9_we', 't12_wd', 't12_we', 't24_wd', 't24_we'],
            'weekly' => ['weekly'],
            'monthly' => ['monthly'],
            'daily' => ['night_wd', 'night_we'],
        ];

        $keys = $priceKeys[$key] ?? [];

        foreach ($this->unit_types ?? [] as $type) {
            foreach ($keys as $k) {
                if (($this->prices[$type][$k] ?? null) !== null) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Booking methods available for this property, in display order.
     * Weekly/monthly are only included when a price is set.
     */
    public function availableBookingTypes(): array
    {
        $out = [];

        foreach (['transit' => 'Transit', 'daily' => 'Harian', 'weekly' => 'Mingguan', 'monthly' => 'Bulanan'] as $key => $label) {
            if ($this->hasBookingType($key)) {
                $out[$key] = $label;
            }
        }

        return $out;
    }

    /**
     * Maximum booking duration in days (null = unlimited).
     */
    public function maxBookingDays(): ?int
    {
        $max = $this->max_days;

        return $max > 0 ? (int) $max : null;
    }

    /**
     * Nearby places grouped by category, filtered to non-empty groups.
     * Supports both legacy categories and the new expanded category set.
     *
     * Category display order and emoji mapping is defined here.
     */
    public const NEARBY_CATEGORIES = [
        'Mall/Shopping'            => '🛍️',
        'Restaurant/Food'          => '🍽️',
        'Transport'                => '🚆',
        'Education'                => '🎓',
        'Hospital/Health'          => '🏥',
        'Recreation'               => '🎡',
        'Hotel'                    => '🏨',
        'Nearby Places'            => '📍',
        'Transportation'           => '🚌',
        'Entertainment/Attraction' => '🎭',
        'Others'                   => '📌',
    ];

    /**
     * Nearby places grouped by category, filtered to non-empty groups.
     */
    public function nearbyByCategory(): array
    {
        // Initialize groups in the canonical display order
        $groups = array_fill_keys(array_keys(self::NEARBY_CATEGORIES), []);

        foreach ((array) ($this->nearby_places ?? []) as $place) {
            $cat = $place['category'] ?? 'Others';
            if (!array_key_exists($cat, $groups)) {
                $cat = 'Others';
            }
            $groups[$cat][] = $place;
        }

        return array_filter($groups, fn ($items) => count($items) > 0);
    }

    /**
     * Auto-generated FAQ entries derived from property data + booking rules.
     */
    public function faqs(): array
    {
        $faqs = [];
        $types = $this->unit_types ?? [];

        if ($types) {
            $labels = array_map(fn ($t) => $this->typeLabel($t), $types);
            $faqs[] = [
                'q' => 'Tipe kamar apa saja yang tersedia?',
                'a' => 'Kamar yang tersedia di apartemen ini: ' . implode(', ', $labels) . '. Pilih tipe kamar saat melakukan pemesanan.',
            ];
        }

        if ($this->checkin_time || $this->checkout_time) {
            $time = trim(($this->checkin_time ?: '—') . ' s/d ' . ($this->checkout_time ?: '—'), ' —');
            $faqs[] = [
                'q' => 'Jam berapa check-in dan check-out?',
                'a' => 'Check-in mulai pukul ' . ($this->checkin_time ?: '-') . ' dan check-out paling lambat pukul ' . ($this->checkout_time ?: '-') . ' WIB.',
            ];
        }

        if ($this->checkin_method) {
            $faqs[] = [
                'q' => 'Bagaimana proses check-in?',
                'a' => $this->checkin_method,
            ];
        }

        if ($docs = $this->required_documents ?? []) {
            $faqs[] = [
                'q' => 'Dokumen apa saja yang harus disiapkan saat check-in?',
                'a' => 'Dokumen yang diperlukan: ' . implode(', ', $docs) . '.',
            ];
        }

        if ($max = $this->maxBookingDays()) {
            $faqs[] = [
                'q' => 'Berapa lama maksimal saya bisa menyewa?',
                'a' => 'Durasi maksimal pemesanan adalah ' . $max . ' malam.',
            ];
        }

        if ($this->hasBookingType('daily')) {
            $faqs[] = [
                'q' => 'Bagaimana cara menghitung harga?',
                'a' => 'Harga dihitung per malam. Tarif weekday dan weekend bisa berbeda — tarif weekend berlaku pada hari yang sudah ditetapkan properti.',
            ];
        }

        $faqs[] = [
            'q' => 'Apakah ada uang deposit?',
            'a' => 'Deposit 30% dari total harga diperlukan untuk konfirmasi pemesanan. Sisanya dibayar saat check-in.',
        ];

        return $faqs;
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
