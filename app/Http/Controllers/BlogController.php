<?php

namespace App\Http\Controllers;

use App\Models\Post;
use App\Models\Category;
use App\Models\Tag;
use App\Services\SettingsService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class BlogController extends Controller
{
    public function index()
    {
        $posts = Post::with(['category', 'tags', 'author'])
                    ->published()
                    ->orderBy('published_at', 'desc')
                    ->paginate(12);

        $sidebarData = $this->getSidebarData();

        // Base page title only; SeoService::title() appends " - {Site Name}".
        $seo = \App\Services\SeoService::metaTags(
            'Blog',
            'Read our latest articles and updates',
            url('/blog'),
        );

        return view('blog.index', array_merge(compact('posts', 'seo'), $sidebarData));
    }

    public function show(string $slug)
    {
        $post = Post::with(['category', 'tags', 'author', 'seo'])
                    ->where('slug', $slug)
                    ->firstOrFail();

        if ($post->status !== 'published') {
            abort(404);
        }

        $sidebarData = $this->getSidebarData();

        $relatedPosts = Post::published()
                            ->where('category_id', $post->category_id)
                            ->where('id', '!=', $post->id)
                            ->latest('published_at')
                            ->limit(3)
                            ->get();

        // Ponytail: bila post punya seo metadata kustom, dipakai langsung;
        // fallback ke metaTags() dari title/excerpt bila kosong.
        // Sertakan featured image (absolut via SeoService) agar preview sosial kaya.
        $postImage = $post->featured_image
            ? \Illuminate\Support\Facades\Storage::disk('public')->url($post->featured_image)
            : '';
        $seo = $post->seo
            ? \App\Services\SeoService::metaTagsArray($post)
            : \App\Services\SeoService::metaTags(
                $post->title,
                Str::limit(strip_tags($post->excerpt ?? $post->content), 160),
                url('/blog/' . $post->slug),
                $postImage,
                'article',
            );

        return view('blog.show', array_merge(compact('post', 'relatedPosts', 'seo'), $sidebarData));
    }

    public function category(string $slug)
    {
        $category = Category::where('slug', $slug)->firstOrFail();

        $posts = Post::with(['category', 'tags', 'author'])
                    ->published()
                    ->where('category_id', $category->id)
                    ->orderBy('published_at', 'desc')
                    ->paginate(12);

        $sidebarData = $this->getSidebarData();

        $seo = \App\Services\SeoService::metaTags(
            'Category: ' . $category->name . ' - Blog',
            'Posts in category ' . $category->name,
            url('/blog/category/' . $category->slug),
        );

        return view('blog.index', array_merge(compact('posts', 'category', 'seo'), $sidebarData));
    }

    public function tag(string $slug)
    {
        $tag = Tag::where('slug', $slug)->firstOrFail();

        $posts = Post::with(['category', 'tags', 'author'])
                    ->published()
                    ->whereHas('tags', fn($q) => $q->where('slug', $slug))
                    ->orderBy('published_at', 'desc')
                    ->paginate(12);

        $sidebarData = $this->getSidebarData();

        $seo = \App\Services\SeoService::metaTags(
            'Tag: ' . $tag->name . ' - Blog',
            'Posts tagged ' . $tag->name,
            url('/blog/tag/' . $tag->slug),
        );

        return view('blog.index', array_merge(compact('posts', 'tag', 'seo'), $sidebarData));
    }

    protected function getSidebarData(): array
    {
        // BUG-018 FIX: Gunakan cache tag 'blog' agar bisa di-invalidate
        // secara presisi saat post/kategori/tag berubah, bukan tunggu 1 jam.
        // Tag-based cache membutuhkan driver yang mendukung tags (Redis/Memcached).
        // Fallback ke remember() biasa jika driver tidak support tags (file/database).
        try {
            return \Illuminate\Support\Facades\Cache::tags(['blog'])
                ->remember('blog_sidebar', now()->addHour(), function () {
                    return [
                        'recentPosts' => Post::published()->latest('published_at')->limit(5)->get(),
                        'categories'  => Category::withCount(['posts' => fn($q) => $q->published()])->orderBy('name')->get(),
                        'tags'        => Tag::withCount(['posts' => fn($q) => $q->published()])->orderBy('name')->get(),
                    ];
                });
        } catch (\BadMethodCallException $e) {
            // Driver tidak support cache tags (file/database) — fallback ke remember biasa
            return \Illuminate\Support\Facades\Cache::remember('blog_sidebar', now()->addHour(), function () {
                return [
                    'recentPosts' => Post::published()->latest('published_at')->limit(5)->get(),
                    'categories'  => Category::withCount(['posts' => fn($q) => $q->published()])->orderBy('name')->get(),
                    'tags'        => Tag::withCount(['posts' => fn($q) => $q->published()])->orderBy('name')->get(),
                ];
            });
        }
    }
}
