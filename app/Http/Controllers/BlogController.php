<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Post;
use App\Models\Tag;
use App\Services\BlogPropertyService;
use App\Services\SchemaService;
use App\Services\SeoService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
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
            route('blog.index'),
            [],
            [
                'jsonld' => [
                    SchemaService::organization(),
                    SchemaService::website(),
                    SchemaService::collectionPage(
                        'Blog',
                        'Read our latest articles and updates',
                        route('blog.index'),
                        $posts->getCollection()->values()->map(fn (Post $item, int $index): array => [
                            '@type' => 'ListItem',
                            'position' => $index + 1,
                            'url' => route('blog.show', $item->slug),
                            'name' => $item->title,
                        ])->all(),
                    ),
                ],
            ],
        );

        return view('blog.index', array_merge(compact('posts', 'seo'), $sidebarData));
    }

    public function show(string $slug)
    {
        // Public pillar/cluster: only published rows ever reach the view
        // (draft pillar/cluster stay invisible without extra queries).
        $post = Post::with([
            'category', 'tags', 'author', 'seo',
            'pillar' => fn ($q) => $q->published(),
            'clusterPosts' => fn ($q) => $q->published()
                ->orderBy('published_at')
                ->limit((int) config('blog.cluster_posts_limit', 8)),
        ])
            ->where('slug', $slug)
            ->firstOrFail();

        if ($post->status !== 'published') {
            abort(404);
        }

        $sidebarData = $this->getSidebarData();

        // Dedup related posts against the pillar/cluster modules: posts the
        // visitor already sees in "part of guide" / "in this guide" sections
        // are not repeated as related recommendations.
        $relatedPosts = $this->dedupPillarRelated($this->getRelatedPosts($post), $post);

        // Eager-loaded (published-constrained) pillar modules for the view.
        $pillar = $post->pillar;
        $clusterPosts = $post->clusterPosts;

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
                route('blog.show', $post->slug),
                $postImage,
                'article',
            );
        $seo['jsonld'] = [
            SchemaService::organization(),
            SchemaService::website(),
            SchemaService::postArticle($post, route('blog.show', $post->slug)),
            SchemaService::breadcrumbList([
                'Home' => url('/'),
                'Blog' => route('blog.index'),
                $post->title => route('blog.show', $post->slug),
            ]),
        ];

        // Property CTA: matched via the post's location tags (service-owned
        // logic), falling back to featured properties inside the service.
        $blogPropertyService = app(BlogPropertyService::class);
        $ctaProperties = $blogPropertyService->forPost($post, (int) config('blog.article_cta_limit', 3));
        $ctaAreaLabel = $this->ctaAreaLabel($post);

        return view('blog.show', array_merge(
            compact('post', 'relatedPosts', 'seo', 'ctaProperties', 'ctaAreaLabel', 'pillar', 'clusterPosts'),
            $sidebarData
        ));
    }

    /**
     * Human area label for the article CTA heading ("Apartemen Tersedia di X"),
     * derived from the post's location tags via the config map. Generic label
     * when no location tag is present. Pure lookup — no DB access.
     */
    protected function ctaAreaLabel(Post $post): string
    {
        $map = (array) config('blog.location_tag_to_city', []);

        foreach ($post->tags as $tag) {
            if (array_key_exists($tag->slug, $map) && ! empty($map[$tag->slug])) {
                return (string) $tag->name;
            }
        }

        return '';
    }

    /**
     * Related posts for the article page: same category first, then a
     * shared-tag fallback to fill up to $limit.
     *
     * Two bounded queries total:
     *  1. published posts in the same category (existing behaviour),
     *  2. published posts sharing at least one of the post's tags
     *     (single `whereHas` against the post_tag pivot — no per-tag loop),
     *     excluding the current post and everything already picked.
     *
     * The current post is always excluded; drafts never appear
     * (`published()` scope on both queries); max $limit results.
     *
     * @return Collection<int, Post>
     */
    protected function getRelatedPosts(Post $post, int $limit = 3): Collection
    {
        $related = Post::published()
            ->where('category_id', $post->category_id)
            ->where('id', '!=', $post->id)
            ->latest('published_at')
            ->limit($limit)
            ->get();

        if ($related->count() >= $limit) {
            return $related;
        }

        $excludeIds = $related->pluck('id')
            ->push($post->id)
            ->all();

        $fallback = Post::published()
            ->whereHas('tags', fn ($q) => $q->whereIn('tags.id', $post->tags->modelKeys()))
            ->whereNotIn('id', $excludeIds)
            ->latest('published_at')
            ->take($limit - $related->count())
            ->get();

        return $related->concat($fallback)->values();
    }

    /**
     * Related-post dedup for the pillar/cluster modules: drop related
     * candidates the visitor already sees in the "part of guide" (pillar)
     * or "in this guide" (cluster) sections. Collection-level only —
     * no extra queries, related-post logic itself is untouched.
     *
     * @param  Collection<int, Post>  $relatedPosts
     * @return Collection<int, Post>
     */
    protected function dedupPillarRelated(Collection $relatedPosts, Post $post): Collection
    {
        $excludedIds = collect([$post->pillar?->id])
            ->merge($post->clusterPosts->modelKeys())
            ->filter()
            ->all();

        if ($excludedIds === []) {
            return $relatedPosts;
        }

        return $relatedPosts
            ->reject(fn (Post $item) => in_array($item->id, $excludedIds))
            ->values();
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

        // Archive landing-page SEO: description dari Category.description,
        // fallback i18n; schema CollectionPage + BreadcrumbList. Admin dapat
        // meng-override via System Pages (`blog.category`).
        $description = $category->description
            ?: __('blog.category_meta_description', ['name' => $category->name]);

        $seo = SeoService::forSystemPage(
            'blog.category',
            __('blog.category_meta_title', ['name' => $category->name]),
            $description,
            route('blog.category', $category->slug),
            ['name' => $category->name],
            [
                'jsonld' => [
                    SchemaService::organization(),
                    SchemaService::website(),
                    SchemaService::collectionPage(
                        $category->name,
                        $description,
                        route('blog.category', $category->slug),
                        $this->listItems($posts),
                    ),
                    SchemaService::breadcrumbList([
                        'Home' => url('/'),
                        __('blog.title') => route('blog.index'),
                        $category->name => route('blog.category', $category->slug),
                    ]),
                ],
            ],
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

        // Description dari Tag.description (nullable), fallback i18n.
        $description = $tag->description
            ?: __('blog.tag_meta_description', ['name' => $tag->name]);

        $seo = SeoService::forSystemPage(
            'blog.tag',
            __('blog.tag_meta_title', ['name' => $tag->name]),
            $description,
            route('blog.tag', $tag->slug),
            ['name' => $tag->name],
            [
                'jsonld' => [
                    SchemaService::organization(),
                    SchemaService::website(),
                    SchemaService::collectionPage(
                        $tag->name,
                        $description,
                        route('blog.tag', $tag->slug),
                        $this->listItems($posts),
                    ),
                    SchemaService::breadcrumbList([
                        'Home' => url('/'),
                        __('blog.title') => route('blog.index'),
                        $tag->name => route('blog.tag', $tag->slug),
                    ]),
                ],
            ],
        );

        // Tag → property discovery via BlogPropertyService (Fase 1):
        // location tags resolve ke city; tag lain fallback ke featured.
        $tagProperties = app(BlogPropertyService::class)
            ->propertiesForTag($tag, (int) config('blog.tag_properties_limit', 3));

        return view('blog.index', array_merge(compact('posts', 'tag', 'seo', 'tagProperties'), $sidebarData));
    }

    /**
     * ListItem map for the archive's CollectionPage.mainEntity, mirroring
     * the blog index / property listing schema shape.
     *
     * @param  LengthAwarePaginator<int, Post>  $posts
     * @return array<int, array{ '@type': string, position: int, url: string, name: string }>
     */
    protected function listItems($posts): array
    {
        return $posts->getCollection()
            ->values()
            ->map(fn (Post $item, int $index): array => [
                '@type' => 'ListItem',
                'position' => $index + 1,
                'url' => route('blog.show', $item->slug),
                'name' => $item->title,
            ])
            ->all();
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
     * $featuredProperties mengikuti pola cache 'blog_sidebar' yang sama
     * (TTL 1 jam, invalidasi via Post/Category/Tag saved/deleted events);
     * data properti sendiri di-cache terpisah di BlogPropertyService
     * dengan TTL 1 jam juga.
     *
     * @return array{recentPosts: Collection, categories: Collection, tags: Collection, featuredProperties: Collection}
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
            'featuredProperties' => app(BlogPropertyService::class)
                ->featured((int) config('blog.sidebar_properties_limit', 3)),
        ];
    }
}
