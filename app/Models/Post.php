<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
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
        'pillar_post_id',
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

    /**
     * The pillar post this (cluster) article belongs to. Null = standalone
     * post or a pillar itself. Self-references are prevented at validation;
     * a deleted pillar nulls this column via the FK (nullOnDelete).
     */
    public function pillar()
    {
        return $this->belongsTo(Post::class, 'pillar_post_id');
    }

    /**
     * Cluster articles that belong to this post as their pillar.
     * Unconstrained base relationship (admin sees drafts too);
     * public rendering filters published at the query site.
     */
    public function clusterPosts()
    {
        return $this->hasMany(Post::class, 'pillar_post_id');
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
            if ($post->status === 'published' && ! $post->published_at) {
                $post->published_at = now();
            }
        });

        static::updating(function (Post $post) {
            if ($post->isDirty('status') && $post->status === 'published' && ! $post->published_at) {
                $post->published_at = now();
            }
        });

        static::saved(function () {
            Cache::forget('sitemap.xml');
            Cache::forget('blog_sidebar');
            Cache::forget('dashboard_stats');
        });

        static::deleted(function () {
            Cache::forget('sitemap.xml');
            Cache::forget('blog_sidebar');
            Cache::forget('dashboard_stats');
        });
    }

    public static function uniqueSlug(string $title, ?int $excludeId = null): string
    {
        $slug = Str::slug($title);
        $original = $slug;
        $counter = 1;

        while (static::where('slug', $slug)->when($excludeId, fn ($q) => $q->where('id', '!=', $excludeId))->exists()) {
            $slug = $original.'-'.$counter++;
        }

        return $slug;
    }
}
