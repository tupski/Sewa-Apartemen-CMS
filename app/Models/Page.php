<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Page extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'title',
        'slug',
        'excerpt',
        'content',
        'status',
        'is_homepage',
        'layout',
        'blocks',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'is_homepage' => 'boolean',
        'blocks' => 'array',
    ];

    /**
     * The "booted" method of the model.
     */
    protected static function booted(): void
    {
        static::creating(function ($page) {
            if ($page->is_homepage) {
                static::where('is_homepage', true)
                    ->update(['is_homepage' => false]);
            }
        });

        static::updated(function ($page) {
            if ($page->is_homepage) {
                static::where('is_homepage', true)
                    ->where('id', '!=', $page->id)
                    ->update(['is_homepage' => false]);
            }
        });
    }

    /**
     * Get the user that created the page.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the navigation items for this page.
     */
    public function navigations()
    {
        return $this->hasMany(Navigation::class);
    }

    /**
     * Scope a query to only include published pages.
     */
    public function scopePublished($query)
    {
        return $query->where('status', 'published');
    }

    /**
     * Scope a query to only include draft pages.
     */
    public function scopeDraft($query)
    {
        return $query->where('status', 'draft');
    }

    /**
     * Get the SEO metadata for the page.
     */
    public function seo()
    {
        return $this->morphOne(SeoMetadata::class, 'seoable');
    }
}
