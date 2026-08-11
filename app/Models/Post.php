<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Str;

class Post extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'slug',
        'content',
        'excerpt',
        'status',
        'published_at',
        'user_id',
        'category_id',
        'featured_image',
    ];

    protected $casts = [
        'published_at' => 'datetime',
    ];

    public function author()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function tags()
    {
        return $this->belongsToMany(Tag::class, 'post_tag');
    }

    public function seo()
    {
        return $this->morphOne(SeoMetadata::class, 'seoable');
    }

    public function scopePublished($query)
    {
        return $query->where('status', 'published')
                     ->whereNotNull('published_at')
                     ->where('published_at', '<=', now());
    }

    protected static function booted(): void
    {
        static::creating(function (Post $post) {
            if (empty($post->slug)) {
                $post->slug = static::uniqueSlug($post->title);
            }
            if ($post->status === 'published' && !$post->published_at) {
                $post->published_at = now();
            }
        });

        static::updating(function (Post $post) {
            if ($post->isDirty('status') && $post->status === 'published' && !$post->published_at) {
                $post->published_at = now();
            }
        });

        static::saved(function () {
            \Illuminate\Support\Facades\Cache::forget('sitemap.xml');
            \Illuminate\Support\Facades\Cache::forget('blog_sidebar');
            \Illuminate\Support\Facades\Cache::forget('dashboard_stats');
        });

        static::deleted(function () {
            \Illuminate\Support\Facades\Cache::forget('sitemap.xml');
            \Illuminate\Support\Facades\Cache::forget('blog_sidebar');
            \Illuminate\Support\Facades\Cache::forget('dashboard_stats');
        });
    }

    public static function uniqueSlug(string $title, ?int $excludeId = null): string
    {
        $slug = Str::slug($title);
        $original = $slug;
        $counter = 1;

        while (static::where('slug', $slug)->when($excludeId, fn($q) => $q->where('id', '!=', $excludeId))->exists()) {
            $slug = $original . '-' . $counter++;
        }

        return $slug;
    }
}
