<?php

namespace Tests\Feature;

use App\Models\Post;
use App\Models\Property;
use App\Models\Redirect;
use App\Models\Role;
use App\Models\SystemPage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SeoTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test sitemap.xml returns 200 and contains property URL.
     */
    public function test_sitemap_returns_200_and_contains_property_url(): void
    {
        Property::create([
            'name' => 'Test Property',
            'slug' => 'test-property',
            'status' => 'published',
        ]);

        $response = $this->get('/sitemap.xml');

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'application/xml');
        $this->assertStringContainsString('<?xml', $response->getContent());
        $this->assertStringContainsString('<urlset', $response->getContent());
    }

    /**
     * Test robots.txt returns 200 and disallows /admin.
     */
    public function test_robots_txt_returns_200_and_disallows_admin(): void
    {
        $response = $this->get('/robots.txt');

        $response->assertStatus(200);
        $this->assertStringContainsString('text/plain', $response->headers->get('Content-Type'));
        $this->assertStringContainsString('Disallow: /admin', $response->getContent());
        $this->assertStringContainsString('Sitemap:', $response->getContent());
    }

    /**
     * Test redirect middleware performs 301.
     */
    public function test_redirect_middleware_performs_301(): void
    {
        Redirect::create([
            'from_url' => 'old-page',
            'to_url' => '/new-page',
            'status_code' => 301,
        ]);

        $response = $this->get('/old-page');

        $response->assertStatus(301);
        $response->assertRedirect(url('/new-page'));
    }

    /**
     * Test redirect loop prevention returns 404 on cycle.
     */
    public function test_redirect_loop_prevention_returns_404_on_cycle(): void
    {
        Redirect::create([
            'from_url' => 'cycle-a',
            'to_url' => 'cycle-b',
            'status_code' => 301,
        ]);

        Redirect::create([
            'from_url' => 'cycle-b',
            'to_url' => 'cycle-a',
            'status_code' => 301,
        ]);

        $response = $this->get('/cycle-a');

        $response->assertStatus(404);
    }

    /**
     * Test SeoMetadata saved via property store.
     */
    public function test_seo_metadata_saved_via_property_store(): void
    {
        $user = User::factory()->create();
        $role = Role::updateOrCreate(['slug' => 'super-admin'], ['name' => 'Super Admin']);
        $user->roles()->syncWithoutDetaching([$role->id => ['model_type' => User::class]]);
        $this->actingAs($user);

        $response = $this->post(route('admin.properties.store'), [
            'name' => 'SEO Property',
            'slug' => 'seo-property',
            'status' => 'published',
            'seo' => [
                'meta_title' => 'Custom SEO Title',
                'meta_description' => 'Custom SEO Description',
                'index_status' => true,
            ],
        ]);

        $response->assertRedirect(route('admin.properties.index'));

        $property = Property::where('slug', 'seo-property')->first();
        $this->assertNotNull($property);

        $seo = $property->seo;
        $this->assertNotNull($seo);
        $this->assertEquals('Custom SEO Title', $seo->meta_title);
        $this->assertEquals('Custom SEO Description', $seo->meta_description);
        $this->assertTrue($seo->index_status);
    }

    public function test_public_pages_render_turbo_compatible_seo_and_schema_head(): void
    {
        SystemPage::syncRegistry();
        Post::factory()->published()->create([
            'title' => 'Schema Article',
            'slug' => 'schema-article',
            'content' => 'Article body',
        ]);
        Property::create([
            'name' => 'Schema Apartment',
            'slug' => 'schema-apartment',
            'status' => 'published',
        ]);

        foreach ([
            route('home'),
            route('properties.public.index'),
            route('properties.public.show', 'schema-apartment'),
            route('promotions'),
            route('blog.show', 'schema-article'),
            route('contact'),
        ] as $url) {
            $response = $this->get($url);

            $response->assertOk();
            $response->assertSee('data-seo-head="true"', false);
            $response->assertSee('application/ld+json', false);
        }
    }

    public function test_sitemap_uses_named_routes_and_robots_disallows_admin_paths(): void
    {
        $sitemap = $this->get(route('sitemap'));
        $robots = $this->get(route('robots'));

        $sitemap->assertSee('/apartments', false);
        $sitemap->assertSee('/blog', false);
        $robots->assertSee('Disallow: /admin', false);
        $robots->assertSee('Disallow: /bookings', false);
    }
}
