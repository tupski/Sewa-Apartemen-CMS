<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Post;
use App\Models\Tag;
use App\Services\SeoService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
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
        // Admin overrides live in admin Pages → System Pages (`blog.index`).
        $seo = SeoService::forSystemPage(
            'blog.index',
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
            ? Storage::disk('public')->url($post->featured_image)
            : '';
        $seo = $post->seo
            ? SeoService::metaTagsArray($post)
            : SeoService::metaTags(
                $post->title,
                Str::limit(strip_tags($post->excerpt ?? $post->content), 160),
                url('/blog/'.$post->slug),
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

        $seo = SeoService::metaTags(
            'Category: '.$category->name.' - Blog',
            'Posts in category '.$category->name,
            url('/blog/category/'.$category->slug),
        );

        return view('blog.index', array_merge(compact('posts', 'category', 'seo'), $sidebarData));
    }

    public function tag(string $slug)
    {
        $tag = Tag::where('slug', $slug)->firstOrFail();

        $posts = Post::with(['category', 'tags', 'author'])
            ->published()
            ->whereHas('tags', fn ($q) => $q->where('slug', $slug))
            ->orderBy('published_at', 'desc')
            ->paginate(12);

        $sidebarData = $this->getSidebarData();

        $seo = SeoService::metaTags(
            'Tag: '.$tag->name.' - Blog',
            'Posts tagged '.$tag->name,
            url('/blog/tag/'.$tag->slug),
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
            return Cache::tags(['blog'])
                ->remember('blog_sidebar', now()->addHour(), fn (): array => $this->buildSidebarData());
        } catch (\BadMethodCallException $e) {
            // Driver tidak support cache tags (file/database) — fallback ke remember biasa
            return Cache::remember('blog_sidebar', now()->addHour(), fn (): array => $this->buildSidebarData());
        }
    }

    /**
     * Sidebar payload.
     *
     * Kategori HANYA disertakan bila punya minimal satu post published —
     * `whereHas()` memfilter di SQL, jadi kategori kosong tidak pernah sampai
     * ke view (sidebar menyembunyikan blok Kategori bila koleksinya kosong).
     *
     * @return array{recentPosts: Collection, categories: Collection, tags: Collection}
     */
    protected function buildSidebarData(): array
    {
        return [
            'recentPosts' => Post::published()->latest('published_at')->limit(5)->get(),
            'categories' => Category::query()
                ->whereHas('posts', fn ($q) => $q->published())
                ->withCount(['posts' => fn ($q) => $q->published()])
                ->orderBy('name')
                ->get(),
            'tags' => Tag::withCount(['posts' => fn ($q) => $q->published()])->orderBy('name')->get(),
        ];
    }
}
