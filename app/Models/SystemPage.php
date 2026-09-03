<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphOne;

/**
 * A non-CMS route (homepage, apartment listing, apartment detail template,
 * blog index, contact, promotions) that an admin can attach SEO metadata to.
 *
 * This model stores NO content — only the route identity. The title,
 * description, Open Graph and Twitter values live on the existing polymorphic
 * `seo_metadata` table via the `seoable` morph, exactly like Page, Post and
 * Property, so the application still has a single SEO storage table.
 */
class SystemPage extends Model
{
    use HasFactory;

    /**
     * Registry of manageable routes: key => [label, sort_order].
     *
     * The key is a stable machine identifier used by the controllers when they
     * ask SeoService for an override. Adding a key here and re-running
     * {@see static::syncRegistry()} is all that is needed to expose a new route
     * in the admin.
     *
     * @var array<string, array{label: string, sort_order: int}>
     */
    public const REGISTRY = [
        'home' => ['label' => 'Homepage', 'sort_order' => 10],
        'properties.index' => ['label' => 'Daftar Apartemen', 'sort_order' => 20],
        'properties.show' => ['label' => 'Detail Apartemen (template)', 'sort_order' => 30],
        'blog.index' => ['label' => 'Blog', 'sort_order' => 40],
        'promotions' => ['label' => 'Promo', 'sort_order' => 50],
        'contact' => ['label' => 'Kontak', 'sort_order' => 60],
    ];

    /**
     * Keys whose title/description accept placeholders because the route renders
     * many different records.
     *
     * @var array<int, string>
     */
    public const TEMPLATE_KEYS = ['properties.show'];

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'key',
        'label',
        'sort_order',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'sort_order' => 'integer',
    ];

    /**
     * Get the SEO metadata for this route.
     */
    public function seo(): MorphOne
    {
        return $this->morphOne(SeoMetadata::class, 'seoable');
    }

    /**
     * Create any registry entry that is missing and refresh drifted labels.
     *
     * Idempotent, so it is safe to call on every admin Pages visit. This keeps
     * existing installs working without requiring a seeder run.
     */
    public static function syncRegistry(): void
    {
        foreach (static::REGISTRY as $key => $meta) {
            static::updateOrCreate(
                ['key' => $key],
                ['label' => $meta['label'], 'sort_order' => $meta['sort_order']]
            );
        }
    }

    /**
     * Whether this route's title/description support placeholders.
     */
    public function supportsPlaceholders(): bool
    {
        return in_array($this->key, static::TEMPLATE_KEYS, true);
    }

    /**
     * Scope: registry order.
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderBy('key');
    }
}
