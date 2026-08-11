<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Cache;

class Navigation extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'parent_id',
        'title',
        'url',
        'page_id',
        'type',
        'target',
        'icon',
        'menu_location',
        'order',
        'status',
        'css_class',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'parent_id' => 'integer',
        'page_id' => 'integer',
        'order' => 'integer',
    ];

    /**
     * Get the parent navigation item.
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(Navigation::class, 'parent_id');
    }

    /**
     * Get the child navigation items.
     */
    public function children(): HasMany
    {
        return $this->hasMany(Navigation::class, 'parent_id')->orderBy('order');
    }

    /**
     * Get the associated page.
     */
    public function page(): BelongsTo
    {
        return $this->belongsTo(Page::class);
    }

    /**
     * Scope a query to only include active navigations.
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Scope a query to only include navigations in a specific location.
     */
    public function scopeInLocation($query, string $location)
    {
        return $query->where('menu_location', $location);
    }

    /**
     * Scope a query to only include root level items.
     */
    public function scopeRootItems($query)
    {
        return $query->whereNull('parent_id');
    }

    /**
     * Scope a query to order by the order field.
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('order', 'asc');
    }

    /**
     * Get the appropriate URL for this navigation item.
     */
    public function getUrlAttribute($value)
    {
        if ($this->type === 'page' && $this->page) {
            return '/' . $this->page->slug;
        }

        return $value;
    }

    /**
     * Check if the navigation item is active.
     */
    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    /**
     * Check if the navigation item has children.
     */
    public function hasChildren(): bool
    {
        return $this->children()->count() > 0;
    }

    protected static function booted(): void
    {
        static::saved(function () {
            Cache::forget('frontend_navigation');
        });

        static::deleted(function () {
            Cache::forget('frontend_navigation');
        });
    }
}
