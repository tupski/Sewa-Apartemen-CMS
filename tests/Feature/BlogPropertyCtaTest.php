<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Post;
use App\Models\Property;
use App\Models\Tag;
use App\Models\User;
use App\Services\BlogPropertyService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class BlogPropertyCtaTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Isolate every test from cached property/sidebar payloads.
        Cache::flush();
    }

    // ==================== Helpers ====================

    private function makePost(string $slug, array $tagSlugs = []): Post
    {
        $category = Category::create(['name' => 'Tips', 'slug' => 'tips-'.$slug]);

        $post = Post::factory()->published()->create([
            'user_id' => User::factory()->create()->id,
            'category_id' => $category->id,
            'slug' => $slug,
        ]);

        foreach ($tagSlugs as $tagSlug) {
            $tag = Tag::firstOrCreate(['slug' => $tagSlug], ['name' => ucfirst($tagSlug)]);
            $post->tags()->syncWithoutDetaching([$tag->id]);
        }

        return $post->refresh();
    }

    private function makeProperty(string $name, string $city, array $overrides = []): Property
    {
        return Property::factory()->create(array_merge([
            'name' => $name,
            'slug' => Str::slug($name),
            'city' => $city,
            'is_featured' => false,
        ], $overrides));
    }

    // ==================== Article CTA ====================

    public function test_article_with_location_tag_shows_matched_property(): void
    {
        $property = $this->makeProperty('Skyhouse BSD', 'Tangerang Selatan');
        $post = $this->makePost('post-bsd', ['bsd-city']);

        $response = $this->get(route('blog.show', $post->slug));

        $response->assertOk();
        $response->assertSee(__('blog.cta_title_area', ['area' => 'Bsd-city']), false);
        $response->assertSee(route('properties.public.show', $property->slug), false);
        $response->assertSee($property->name);
    }

    public function test_article_without_location_tag_falls_back_to_featured_properties(): void
    {
        $featured = $this->makeProperty('Featured Jakarta', 'Jakarta Pusat', ['is_featured' => true]);
        $this->makeProperty('Plain Bekasi', 'Bekasi');
        $post = $this->makePost('post-generic', ['staycation']);

        $response = $this->get(route('blog.show', $post->slug));

        $response->assertOk();
        $response->assertSee(__('blog.cta_title'), false);
        $response->assertSee(route('properties.public.show', $featured->slug), false);
    }

    public function test_article_with_location_tag_without_matching_city_backfills_featured(): void
    {
        // 'bekasi' maps to Bekasi city, but only a featured property in
        // Jakarta Pusat exists → featured fallback must kick in.
        $featured = $this->makeProperty('Featured Fallback', 'Jakarta Pusat', ['is_featured' => true]);
        $post = $this->makePost('post-bekasi-empty', ['bekasi']);

        $response = $this->get(route('blog.show', $post->slug));

        $response->assertOk();
        $response->assertSee(route('properties.public.show', $featured->slug), false);
    }

    // ==================== Sidebar ====================

    public function test_blog_sidebar_shows_featured_properties(): void
    {
        $featured = $this->makeProperty('Sidebar Featured', 'Bekasi', ['is_featured' => true]);
        $this->makePost('sidebar-post');

        $response = $this->get(route('blog.index'));

        $response->assertOk();
        $response->assertSee(__('blog.sidebar_properties'), false);
        $response->assertSee(route('properties.public.show', $featured->slug), false);
        $response->assertSee($featured->name);
    }

    // ==================== Status visibility ====================

    public function test_draft_property_never_appears_on_blog(): void
    {
        $draft = $this->makeProperty('Draft Apartment', 'Tangerang Selatan', ['status' => 'draft']);
        $published = $this->makeProperty('Published Apartment', 'Tangerang Selatan');
        $post = $this->makePost('post-draft-prop', ['bsd-city']);

        $response = $this->get(route('blog.show', $post->slug));

        $response->assertOk();
        $response->assertDontSee(route('properties.public.show', $draft->slug), false);
        $response->assertSee(route('properties.public.show', $published->slug), false);

        // Sidebar as well.
        $listing = $this->get(route('blog.index'));
        $listing->assertOk();
        $listing->assertDontSee(route('properties.public.show', $draft->slug), false);
    }

    public function test_published_property_appears_on_blog(): void
    {
        $published = $this->makeProperty('Visible Apartment', 'Tangerang Selatan');
        $post = $this->makePost('post-visible', ['bsd-city']);

        $response = $this->get(route('blog.show', $post->slug));

        $response->assertOk();
        $response->assertSee(route('properties.public.show', $published->slug), false);
    }

    // ==================== Limits ====================

    public function test_article_cta_is_limited_to_three_properties(): void
    {
        $this->makeProperty('Match One', 'Tangerang Selatan');
        $this->makeProperty('Match Two', 'Tangerang Selatan');
        $this->makeProperty('Match Three', 'Tangerang Selatan');
        $this->makeProperty('Match Four', 'Tangerang Selatan');
        $post = $this->makePost('post-limit', ['bsd-city']);

        $service = app(BlogPropertyService::class);
        $result = $service->forPost($post->refresh(), 3);

        $this->assertCount(3, $result);
    }

    public function test_sidebar_is_limited_to_three_properties(): void
    {
        $this->makeProperty('Feat One', 'Bekasi', ['is_featured' => true]);
        $this->makeProperty('Feat Two', 'Bekasi', ['is_featured' => true]);
        $this->makeProperty('Feat Three', 'Bekasi', ['is_featured' => true]);
        $this->makeProperty('Feat Four', 'Bekasi', ['is_featured' => true]);

        $result = app(BlogPropertyService::class)->featured(3);

        $this->assertCount(3, $result);
    }

    // ==================== Query efficiency ====================

    public function test_article_page_runs_bounded_property_queries(): void
    {
        $this->makeProperty('Perf One', 'Tangerang Selatan');
        $this->makeProperty('Perf Two', 'Tangerang Selatan');
        $post = $this->makePost('post-perf', ['bsd-city']);

        // Cache warm-up happens on the first hit; count queries on the second
        // hit where the service resolves from cache. Property card data must be
        // eager loaded (featuredImage, photos.media, amenities) — not N+1.
        $this->get(route('blog.show', $post->slug))->assertOk();

        $queries = 0;
        DB::listen(function () use (&$queries): void {
            $queries++;
        });

        $this->get(route('blog.show', $post->slug))->assertOk();

        // Sidebar (recent, categories, tags), post, related, seo, session —
        // property data itself comes from cache. A loose upper bound guards
        // against per-property N+1 (2 properties would add 6+ queries).
        $this->assertLessThan(30, $queries, 'Unexpected query explosion: '.$queries);
    }

    // ==================== Cache ====================

    public function test_featured_properties_are_cached(): void
    {
        $service = app(BlogPropertyService::class);

        $this->makeProperty('Cached Featured', 'Bekasi', ['is_featured' => true]);
        $this->assertCount(1, $service->featured(3));

        // A newly added featured property must NOT appear while the cache
        // entry is still fresh — proving the second call was cached.
        $this->makeProperty('Later Featured', 'Bekasi', ['is_featured' => true]);
        $this->assertCount(1, $service->featured(3));

        // Cache cleared → fresh query picks up both properties.
        Cache::flush();
        $this->assertCount(2, $service->featured(3));
    }
}
