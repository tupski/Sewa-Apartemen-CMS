<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Artivo-managed POI category.
 *
 * `slug` is the raw Geoapify place-category key (e.g. `healthcare.hospital`)
 * and is the stable identity used for Places-API filtering. Display labels are
 * localized (`name_id` / `name_en`) and admin-editable — frontend labels never
 * come from the raw Geoapify string.
 */
class PlaceCategory extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'slug',
        'name_id',
        'name_en',
        'icon',
        'color',
        'is_active',
        'sort_order',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    /**
     * Display label for the active locale (falls back to the other language).
     */
    public function label(?string $locale = null): string
    {
        $locale = $locale ?: app()->getLocale();

        return $locale === 'id'
            ? ($this->name_id ?: $this->name_en)
            : ($this->name_en ?: $this->name_id);
    }

    /**
     * Get the places classified under this category.
     */
    public function places()
    {
        return $this->hasMany(Place::class, 'category', 'slug');
    }

    /**
     * Active categories ordered for display.
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderBy('id');
    }

    /**
     * Resolve a category label for a raw Geoapify category slug, tolerating
     * child keys (`public_transport.train` → `public_transport` row or exact
     * slug match). Returns the slug itself when no category row exists.
     */
    public static function labelForSlug(?string $slug, ?string $locale = null): string
    {
        $category = static::resolveForSlug($slug);

        if (! $category) {
            return (string) $slug;
        }

        return $category->label($locale);
    }

    /**
     * Resolve the Font Awesome icon class for a raw Geoapify category slug
     * (same exact-or-parent matching as labelForSlug). Null when unknown or unset.
     */
    public static function iconForSlug(?string $slug): ?string
    {
        return static::resolveForSlug($slug)?->icon;
    }

    /**
     * Find the category row for a raw slug: exact match first, then the
     * top-level parent key (`public_transport.train` → `public_transport`).
     */
    protected static function resolveForSlug(?string $slug): ?self
    {
        if ($slug === null || $slug === '') {
            return null;
        }

        return static::query()
            ->where('slug', $slug)
            ->orWhere('slug', strtok($slug, '.'))
            ->first();
    }
}
