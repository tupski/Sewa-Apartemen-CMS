<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * PropertyUnitType — structured metadata for one canonical unit type on one
 * property.
 *
 * `unit_type` is the SAME canonical string key used by pricing
 * (`properties.prices`), bookings (`bookings.unit_type`), search filters and
 * badges (`properties.unit_types`). This table never becomes a second
 * identity: it only enriches the type with presentation data (display name,
 * description, occupancy, bed configuration, size).
 *
 * Availability still comes from `properties.unit_types`; `is_active` here only
 * controls whether the metadata-rich presentation (description etc.) is shown
 * on the frontend for that type.
 */
class PropertyUnitType extends Model
{
    protected $fillable = [
        'property_id',
        'unit_type',
        'name',
        'description',
        'max_guests',
        'bed_configuration',
        'size',
        'size_unit',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'max_guests' => 'integer',
        'size' => 'float',
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    /**
     * Presentation name: admin display-name override, else the canonical label.
     */
    public function getDisplayNameAttribute(): string
    {
        $custom = trim((string) $this->name);

        if ($custom !== '') {
            return $custom;
        }

        return Property::typeLabel($this->unit_type);
    }

    /**
     * Human size string ("24 m²") or null when unmeasured.
     */
    public function getSizeFormattedAttribute(): ?string
    {
        if ($this->size === null) {
            return null;
        }

        $size = rtrim(rtrim(number_format((float) $this->size, 2, '.', ''), '0'), '.');

        return $size.' '.($this->size_unit === 'sqft' ? 'ft²' : 'm²');
    }

    /**
     * Ordering for admin UI and frontend lists.
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderBy('id');
    }
}
