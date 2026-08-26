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
     * Normalise the stored icon value into valid Font Awesome 6 classes.
     *
     * Accepts bare tokens ("wifi"), FA5 syntax ("fa fa-wifi" / "fas fa-wifi"),
     * or FA6 syntax ("fa-solid fa-wifi") and always returns a valid FA6 class
     * string such as "fa-solid fa-wifi". Returns an empty string when no icon
     * is set so callers can decide on a fallback.
     */
    public function getIconClassAttribute(): string
    {
        $raw = trim((string) ($this->attributes['icon'] ?? ''));

        if ($raw === '') {
            return '';
        }

        $styleAliases = [
            'fas' => 'fa-solid',
            'far' => 'fa-regular',
            'fab' => 'fa-brands',
            'fal' => 'fa-light',
            'fad' => 'fa-duotone',
            'fat' => 'fa-thin',
        ];
        $styleClasses = [
            'fa-solid', 'fa-regular', 'fa-brands',
            'fa-light', 'fa-duotone', 'fa-thin',
        ];

        $style = null;
        $iconToken = null;

        foreach (preg_split('/\s+/', $raw) as $token) {
            if ($token === '' || $token === 'fa') {
                // Drop empty tokens and the FA5 generic "fa" prefix.
                continue;
            }

            if (isset($styleAliases[$token])) {
                $style = $styleAliases[$token];
                continue;
            }

            if (in_array($token, $styleClasses, true)) {
                $style = $token;
                continue;
            }

            if (str_starts_with($token, 'fa-')) {
                $iconToken = $token;
                continue;
            }

            // Bare token such as "wifi".
            $iconToken = 'fa-' . ltrim($token, '-');
        }

        if ($iconToken === null) {
            return '';
        }

        return ($style ?? 'fa-solid') . ' ' . $iconToken;
    }

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
