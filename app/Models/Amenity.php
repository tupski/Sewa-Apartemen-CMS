<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Amenity extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'slug',
        'icon',
        'category',
        'description',
        'is_active',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Get the properties that have this amenity.
     */
    public function properties()
    {
        return $this->belongsToMany(Property::class, 'amenity_property')
                    ->withTimestamps();
    }

    /**
     * Scope a query to only include active amenities.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope a query to only include property amenities.
     */
    public function scopePropertyAmenities($query)
    {
        return $query->where('category', 'property');
    }

    /**
     * Scope a query to only include unit amenities.
     */
    public function scopeUnitAmenities($query)
    {
        return $query->where('category', 'unit');
    }
}
