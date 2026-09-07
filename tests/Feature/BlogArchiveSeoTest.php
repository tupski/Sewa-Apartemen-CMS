<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Post;
use App\Models\Property;
use App\Models\Role;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Fase 3 — category & tag SEO archive:
 * meta dari description + fallback i18n, CollectionPage + BreadcrumbList
 * schema, tag description rendering, tag → property discovery,
 * admin tag description CRUD.
 */
class BlogArchiveSeoTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $role = Role::updateOrCreate(['slug' => 'super-admin'], ['name' => 'Super Admin']);
        $this->user = User::factory()->create();
        $this->user->roles()->syncWithoutDetaching([$role->id => ['model_type' => User::class]]);
    }

    // ------------------------------------------------------------------
    // Category archive
    // ------------------------------------------------------------------

    public function test_category_archive_uses_description_and_collection_page_schema(): void
    {
        $category = Category::create([
            'name' => 'Tips Sewa Apartemen',
            'slug' => 'tips-sewa-apartemen',
            'description' => 'Panduan praktis menyewa apartemen.',
        ]);

        $post = Post::factory()->published()->create(['category_id' => $category->id]);
        Post::factory()->create(['category_id' => $category->id]); // draft, hidden

        $response = $this->get(route('blog.category', $category->slug));

        $response->assertOk();
        // Title: translation format + site-name suffix appended by SeoService
        // (&amp; is how the raw HTML renders the title's ampersand).
        $response->assertSee('Tips Sewa Apartemen - Panduan &amp; Artikel', false);
        // Category description wins over the i18n fallback.
        $response->assertSee('Panduan praktis menyewa apartemen.', false);
        // CollectionPage + ItemList schema with the published post only.
        // JSON-LD is rendered pretty-printed, hence the space after the colon.
        $response->assertSee('"@type": "CollectionPage"', false);
        $response->assertSee(route('blog.show', $post->slug), false);
        // BreadcrumbList present.
        $response->assertSee('"@type": "BreadcrumbList"', false);
    }

    public function test_category_archive_falls_back_when_description_null(): void
    {
        $category = Category::create(['name' => 'Review Properti', 'slug' => 'review-properti']);

        $response = $this->get(route('blog.category', $category->slug));

        $response->assertOk();
        $response->assertSee(__('blog.category_meta_description', ['name' => 'Review Properti']), false);
    }

    // ------------------------------------------------------------------
    // Tag archive
    // ------------------------------------------------------------------

    public function test_tag_archive_shows_description_and_uses_it_for_meta(): void
    {
        $tag = Tag::create([
            'name' => 'BSD City',
            'slug' => 'bsd-city',
            'description' => 'Panduan apartemen di kawasan BSD City.',
        ]);

        $category = Category::create(['name' => 'Panduan Wisata', 'slug' => 'panduan-wisata']);
        $post = Post::factory()->published()->create(['category_id' => $category->id]);
        $post->tags()->attach($tag);

        $response = $this->get(route('blog.tag', $tag->slug));

        $response->assertOk();
        $response->assertSee('Panduan apartemen di kawasan BSD City.', false);
        $response->assertSee('"@type": "CollectionPage"', false);
        $response->assertSee('"@type": "BreadcrumbList"', false);
        $response->assertSee(route('blog.show', $post->slug), false);
    }

    public function test_tag_archive_falls_back_when_description_null(): void
    {
        $tag = Tag::create(['name' => 'Studio', 'slug' => 'studio']);

        $response = $this->get(route('blog.tag', $tag->slug));

        $response->assertOk();
        $response->assertSee(__('blog.tag_meta_description', ['name' => 'Studio']), false);
        // No description paragraph rendered when null (layout search overlay
        // also uses max-w-3xl, so assert on the full paragraph class instead).
        $this->assertStringNotContainsString('mt-2 text-gray-600 dark:text-gray-300', $response->getContent());
    }

    public function test_tag_archive_shows_relevant_properties_for_location_tag(): void
    {
        $tag = Tag::create([
            'name' => 'BSD City',
            'slug' => 'bsd-city',
            'description' => 'Panduan apartemen di kawasan BSD City.',
        ]);

        // 'bsd-city' maps to 'Tangerang Selatan' in config('blog.location_tag_to_city').
        $match = Property::factory()->create(['city' => 'Tangerang Selatan']);
        $other = Property::factory()->create(['city' => 'Bekasi']);
        Property::factory()->create(['city' => 'Tangerang Selatan', 'status' => 'draft']);

        $response = $this->get(route('blog.tag', $tag->slug));

        $response->assertOk();
        $response->assertSee(route('properties.public.show', $match->slug), false);
        $response->assertDontSee(route('properties.public.show', $other->slug), false);
        $response->assertSee(__('blog.cta_title_area', ['area' => 'BSD City']), false);
    }

    public function test_tag_archive_property_section_hidden_without_results(): void
    {
        $tag = Tag::create(['name' => 'Studio', 'slug' => 'studio']);

        $response = $this->get(route('blog.tag', $tag->slug));

        $response->assertOk();
        $response->assertDontSee('tag-property-cta', false);
    }

    // ------------------------------------------------------------------
    // Admin tag description CRUD
    // ------------------------------------------------------------------

    public function test_admin_can_store_tag_description(): void
    {
        $response = $this->actingAs($this->user)->post(route('admin.tags.store'), [
            'name' => 'Staycation',
            'slug' => 'staycation',
            'description' => 'Ide staycation hemat di apartemen.',
        ]);

        $response->assertRedirect(route('admin.tags.index'));

        $tag = Tag::where('slug', 'staycation')->firstOrFail();
        $this->assertSame('Ide staycation hemat di apartemen.', $tag->description);
    }

    public function test_admin_tag_description_is_nullable(): void
    {
        $response = $this->actingAs($this->user)->put(route('admin.tags.update', Tag::create([
            'name' => 'Keluarga',
            'slug' => 'keluarga',
            'description' => 'Lama',
        ])), [
            'name' => 'Keluarga',
            'slug' => 'keluarga',
            'description' => '',
        ]);

        $response->assertRedirect(route('admin.tags.index'));
        $this->assertNull(Tag::where('slug', 'keluarga')->firstOrFail()->description);
    }

    public function test_admin_tag_store_requires_authentication(): void
    {
        $this->post(route('admin.tags.store'), ['name' => 'X', 'slug' => 'x'])
            ->assertRedirect(route('login'));
    }
}
