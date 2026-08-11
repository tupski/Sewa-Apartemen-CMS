<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class Unit extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'property_id',
        'name',
        'slug',
        'unit_type',
        'floor',
        'size_sqm',
        'bedrooms',
        'bathrooms',
        'description',
        'price_per_night',
        'price_per_month',
        'price_per_year',
        'status',
        'featured_image_id',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'floor' => 'integer',
        'size_sqm' => 'decimal:2',
        'bedrooms' => 'integer',
        'bathrooms' => 'integer',
        'price_per_night' => 'decimal:2',
        'price_per_month' => 'decimal:2',
        'price_per_year' => 'decimal:2',
    ];

    /**
     * Get the property that owns the unit.
     */
    public function property()
    {
        return $this->belongsTo(Property::class);
    }

    /**
     * Get the amenities for the unit.
     */
    public function amenities()
    {
        return $this->belongsToMany(Amenity::class, 'amenity_unit')
                    ->withTimestamps();
    }

    /**
     * Get the featured image for the unit.
     */
    public function featuredImage()
    {
        return $this->belongsTo(Media::class, 'featured_image_id');
    }

    /**
     * Scope a query to only include available units.
     */
    public function scopeAvailable($query)
    {
        return $query->where('status', 'available');
    }

    /**
     * Scope a query to only include booked units.
     */
    public function scopeBooked($query)
    {
        return $query->where('status', 'booked');
    }

    /**
     * Scope a query to only include units under maintenance.
     */
    public function scopeMaintenance($query)
    {
        return $query->where('status', 'maintenance');
    }

    /**
     * Get the SEO metadata for the unit.
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
