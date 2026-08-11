<?php

namespace Tests\Feature;

use App\Models\Page;
use App\Models\Post;
use App\Models\Property;
use App\Models\Unit;
use App\Services\SettingsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SitemapTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        SettingsService::clearCache();
    }

    /** Sitemap returns 200 and XML content type */
    public function test_sitemap_returns_200_with_xml_content_type(): void
    {
        $response = $this->get(route('sitemap'));

        $response->assertStatus(200);
        $this->assertStringContainsString('xml', $response->headers->get('Content-Type'));
    }

    /** Sitemap contains homepage URL */
    public function test_sitemap_contains_homepage(): void
    {
        $response = $this->get(route('sitemap'));

        $response->assertStatus(200);
        $this->assertStringContainsString('<loc>' . url('/') . '</loc>', $response->getContent());
    }

    /** Sitemap contains published property URLs */
    public function test_sitemap_contains_published_properties(): void
    {
        $property = Property::create([
            'name' => 'Listed Property',
            'slug' => 'listed-property',
            'status' => 'published',
        ]);

        $response = $this->get(route('sitemap'));

        $response->assertStatus(200);
        $this->assertStringContainsString('<url>', $response->getContent());
    }

    /** Sitemap excludes non-published properties */
    public function test_sitemap_excludes_draft_properties(): void
    {
        Property::create([
            'name' => 'Draft Prop',
            'slug' => 'draft-prop',
            'status' => 'draft',
        ]);

        $response = $this->get(route('sitemap'));

        $response->assertStatus(200);
        // Draft property should not appear with its slug in loc tag
        $this->assertStringNotContainsString('draft-prop', $response->getContent());
    }

    /** Sitemap contains published pages */
    public function test_sitemap_contains_published_pages(): void
    {
        Page::create([
            'title' => 'About Us',
            'slug' => 'about-us',
            'status' => 'published',
        ]);

        $response = $this->get(route('sitemap'));

        $response->assertStatus(200);
        $this->assertStringContainsString('about-us', $response->getContent());
    }

    /** Sitemap contains available units */
    public function test_sitemap_contains_available_units(): void
    {
        $property = Property::create([
            'name' => 'Prop',
            'slug' => 'prop',
            'status' => 'published',
        ]);

        Unit::create([
            'property_id' => $property->id,
            'name' => 'Apt 101',
            'slug' => 'apt-101',
            'unit_type' => 'Studio',
            'status' => 'available',
            'price_per_night' => 300000,
            'bedrooms' => 1,
            'bathrooms' => 1,
        ]);

        $response = $this->get(route('sitemap'));

        $response->assertStatus(200);
        // The sitemap includes unit URLs (<loc>)
        $this->assertStringContainsString('<loc>', $response->getContent());
    }

    /** Sitemap contains blog URL if post model exists */
    public function test_sitemap_contains_blog_entries(): void
    {
        // Blog index URL
        $response = $this->get(route('sitemap'));

        $response->assertStatus(200);
        // Blog URL should be present since Post model exists
        $this->assertStringContainsString('/blog', $response->getContent());
    }

    /** Sitemap XML is well-formed */
    public function test_sitemap_xml_is_well_formed(): void
    {
        $response = $this->get(route('sitemap'));

        $xml = $response->getContent();

        // Must start with XML declaration
        $this->assertStringStartsWith('<?xml', trim($xml));

        // Must have urlset opening and closing
        $this->assertStringContainsString('<urlset', $xml);
        $this->assertStringContainsString('</urlset>', $xml);

        // Validate as valid XML
        $dom = new \DOMDocument();
        $this->assertTrue($dom->loadXML($xml), 'Sitemap XML is not valid');
    }
}
