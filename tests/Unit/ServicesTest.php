<?php

namespace Tests\Unit;

use App\Models\Page;
use App\Models\Post;
use App\Models\Property;
use App\Models\Setting;
use App\Models\Unit;
use App\Services\AnalyticsService;
use App\Services\RobotsService;
use App\Services\SchemaService;
use App\Services\SeoService;
use App\Services\SettingsService;
use App\Services\SitemapService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ServicesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        SettingsService::clearCache();
    }

    // ==================== SettingsService ====================

    public function test_settings_set_and_get(): void
    {
        SettingsService::set('test_key', 'test_value');

        $this->assertEquals('test_value', SettingsService::get('test_key'));
    }

    public function test_settings_get_returns_default_when_missing(): void
    {
        $this->assertEquals('fallback', SettingsService::get('nonexistent', 'fallback'));
    }

    public function test_settings_has(): void
    {
        SettingsService::set('existing_key', 'value');

        $this->assertTrue(SettingsService::has('existing_key'));
        $this->assertFalse(SettingsService::has('nope'));
    }

    public function test_settings_all_returns_all(): void
    {
        SettingsService::set('key1', 'val1');
        SettingsService::set('key2', 'val2');

        $all = SettingsService::all();

        $this->assertArrayHasKey('key1', $all);
        $this->assertArrayHasKey('key2', $all);
    }

    public function test_settings_all_filtered_by_group(): void
    {
        SettingsService::set('g1_key', 'v1', 'group_one');
        SettingsService::set('g2_key', 'v2', 'group_two');

        $groupOne = SettingsService::all('group_one');

        $this->assertArrayHasKey('g1_key', $groupOne);
        $this->assertArrayNotHasKey('g2_key', $groupOne);
    }

    public function test_settings_clear_cache(): void
    {
        SettingsService::set('cache_key', 'before');
        $this->assertEquals('before', SettingsService::get('cache_key'));

        // Force bypass cache: update DB directly and clear
        Setting::where('key', 'cache_key')->update(['value' => 'after']);
        SettingsService::clearCache();

        $this->assertEquals('after', SettingsService::get('cache_key'));
    }

    public function test_settings_determine_type(): void
    {
        $ref = new \ReflectionClass(SettingsService::class);
        $method = $ref->getMethod('determineType');

        $this->assertEquals('string', $method->invoke(null, 'hello'));
        $this->assertEquals('integer', $method->invoke(null, 42));
        $this->assertEquals('boolean', $method->invoke(null, true));
        $this->assertEquals('json', $method->invoke(null, ['a' => 1]));
    }

    // ==================== SeoService ====================

    public function test_seo_title_truncates_to_60_chars(): void
    {
        $title = 'A Very Long Title That Exceeds Sixty Characters By A Large Margin Indeed For Testing';

        $result = SeoService::title($title);

        $this->assertLessThanOrEqual(60, mb_strlen($result));
    }

    public function test_seo_title_appends_site_name(): void
    {
        SettingsService::set('site_name', 'MySite');

        $result = SeoService::title('Hello World');

        $this->assertStringContainsString('MySite', $result);
        $this->assertStringContainsString('|', $result);
    }

    public function test_seo_title_does_not_double_append_site_name(): void
    {
        SettingsService::set('site_name', 'MySite');

        $result = SeoService::title('Hello | MySite');

        // Should not produce "Hello | MySite | MySite"
        $this->assertEquals(1, substr_count($result, 'MySite'));
    }

    public function test_seo_description_truncates_to_160_chars(): void
    {
        $desc = str_repeat('A', 200);

        $result = SeoService::description($desc);

        $this->assertLessThanOrEqual(160, mb_strlen($result));
    }

    public function test_seo_description_shorter_passes_through(): void
    {
        $result = SeoService::description('Short description');

        $this->assertEquals('Short description', $result);
    }

    public function test_seo_canonical_returns_full_url(): void
    {
        $result = SeoService::canonical('/some/path');

        $this->assertStringContainsString(config('app.url'), $result);
        $this->assertStringContainsString('/some/path', $result);
    }

    public function test_seo_open_graph_tags_contains_required_tags(): void
    {
        $tags = SeoService::openGraphTags([
            'title' => 'OG Title',
            'description' => 'OG Desc',
            'image' => 'https://example.com/img.jpg',
        ]);

        $this->assertStringContainsString('og:title', $tags);
        $this->assertStringContainsString('og:description', $tags);
        $this->assertStringContainsString('og:image', $tags);
        $this->assertStringContainsString('og:url', $tags);
        $this->assertStringContainsString('og:type', $tags);
    }

    public function test_seo_twitter_tags_contains_required_tags(): void
    {
        $tags = SeoService::twitterTags([
            'title' => 'TW Title',
            'description' => 'TW Desc',
            'image' => 'https://example.com/img.jpg',
        ]);

        $this->assertStringContainsString('twitter:card', $tags);
        $this->assertStringContainsString('summary_large_image', $tags);
        $this->assertStringContainsString('twitter:title', $tags);
    }

    public function test_seo_meta_tags_array_from_model(): void
    {
        $property = Property::create([
            'name' => 'Test Property',
            'slug' => 'test-property',
            'description' => 'A test property description',
            'status' => 'published',
        ]);

        $meta = SeoService::metaTagsArray($property);

        $this->assertArrayHasKey('title', $meta);
        $this->assertArrayHasKey('description', $meta);
        $this->assertArrayHasKey('canonical', $meta);
        $this->assertArrayHasKey('robots', $meta);
        $this->assertStringContainsString('Test Property', $meta['title']);
    }

    public function test_seo_meta_tags_array_from_array(): void
    {
        $meta = SeoService::metaTagsArray([
            'title' => 'Array Title',
            'description' => 'Array Desc',
        ]);

        $this->assertEquals('index, follow', $meta['robots']);
        $this->assertStringContainsString('Array Title', $meta['title']);
    }

    public function test_seo_legacy_meta_tags(): void
    {
        $meta = SeoService::metaTags('Legacy Title', 'Legacy Desc', '/legacy-url', '/img.jpg');

        $this->assertArrayHasKey('title', $meta);
        $this->assertArrayHasKey('description', $meta);
        $this->assertArrayHasKey('canonical', $meta);
        $this->assertArrayHasKey('image', $meta);
        $this->assertArrayHasKey('jsonld', $meta);
    }

    // ==================== SitemapService ====================

    public function test_sitemap_generates_valid_xml(): void
    {
        Property::create([
            'name' => 'Prop',
            'slug' => 'prop',
            'status' => 'published',
        ]);

        $service = new SitemapService();
        $xml = $service->generate();

        $this->assertStringContainsString('<?xml version="1.0" encoding="UTF-8"?>', $xml);
        $this->assertStringContainsString('<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">', $xml);
        $this->assertStringContainsString('<url>', $xml);
        $this->assertStringContainsString('<loc>', $xml);
        $this->assertStringContainsString('</urlset>', $xml);
    }

    public function test_sitemap_includes_homepage(): void
    {
        $service = new SitemapService();
        $xml = $service->generate();

        $this->assertStringContainsString('<priority>1.0</priority>', $xml);
    }

    public function test_sitemap_includes_published_pages(): void
    {
        Page::create([
            'title' => 'About Us',
            'slug' => 'about-us',
            'status' => 'published',
        ]);

        $service = new SitemapService();
        $xml = $service->generate();

        $this->assertStringContainsString('about-us', $xml);
    }

    public function test_sitemap_excludes_unpublished_pages(): void
    {
        Page::create([
            'title' => 'Draft Page',
            'slug' => 'draft-page',
            'status' => 'draft',
        ]);

        $service = new SitemapService();
        $xml = $service->generate();

        $this->assertStringNotContainsString('draft-page', $xml);
    }

    // ==================== RobotsService ====================

    public function test_robots_disallows_admin_paths(): void
    {
        $service = new RobotsService();
        $txt = $service->generate();

        $this->assertStringContainsString('Disallow: /admin', $txt);
        $this->assertStringContainsString('Disallow: /install', $txt);
        $this->assertStringContainsString('Disallow: /login', $txt);
        $this->assertStringContainsString('Disallow: /dashboard', $txt);
        $this->assertStringContainsString('Disallow: /profile', $txt);
        $this->assertStringContainsString('Disallow: /register', $txt);
    }

    public function test_robots_includes_sitemap_reference(): void
    {
        $service = new RobotsService();
        $txt = $service->generate();

        $this->assertStringContainsString('Sitemap:', $txt);
        $this->assertStringContainsString('/sitemap.xml', $txt);
    }

    public function test_robots_uses_override_when_set(): void
    {
        SettingsService::set('robots_txt', 'User-agent: *' . "\n" . 'Disallow: /');

        $service = new RobotsService();
        $txt = $service->generate();

        $this->assertEquals("User-agent: *\nDisallow: /", $txt);
    }

    public function test_robots_allows_root(): void
    {
        $service = new RobotsService();
        $txt = $service->generate();

        $this->assertStringContainsString('Allow: /', $txt);
    }

    // ==================== SchemaService ====================

    public function test_schema_organization_has_valid_json_ld_structure(): void
    {
        SettingsService::set('site_name', 'Test Org');

        $schema = SchemaService::organization();

        $this->assertEquals('https://schema.org', $schema['@context']);
        $this->assertEquals('Organization', $schema['@type']);
        $this->assertEquals('Test Org', $schema['name']);
        $this->assertArrayHasKey('url', $schema);
    }

    public function test_schema_website_has_valid_structure(): void
    {
        SettingsService::set('site_name', 'Test Site');

        $schema = SchemaService::website();

        $this->assertEquals('WebSite', $schema['@type']);
        $this->assertEquals('Test Site', $schema['name']);
    }

    public function test_schema_real_estate_listing_from_property(): void
    {
        $property = Property::create([
            'name' => 'Test Apt',
            'slug' => 'test-apt',
            'description' => 'A nice apartment',
            'address' => '123 Main St',
            'city' => 'Jakarta',
            'province' => 'DKI Jakarta',
            'postal_code' => '12345',
            'status' => 'published',
        ]);

        $schema = SchemaService::realEstateListing($property);

        $this->assertEquals('RealEstateListing', $schema['@type']);
        $this->assertEquals('Test Apt', $schema['name']);
        $this->assertArrayHasKey('address', $schema);
        $this->assertEquals('Jakarta', $schema['address']['addressLocality']);
    }

    public function test_schema_breadcrumb_list(): void
    {
        $items = [
            'Home' => 'https://example.com',
            'Properties' => 'https://example.com/properties',
            'Test Property' => 'https://example.com/properties/test',
        ];

        $schema = SchemaService::breadcrumbList($items);

        $this->assertEquals('BreadcrumbList', $schema['@type']);
        $this->assertCount(3, $schema['itemListElement']);
        $this->assertEquals('Home', $schema['itemListElement'][0]['name']);
        $this->assertEquals(1, $schema['itemListElement'][0]['position']);
    }

    public function test_schema_article(): void
    {
        $schema = SchemaService::article([
            'headline' => 'Test Article',
            'description' => 'Article description',
            'datePublished' => '2026-01-01',
            'dateModified' => '2026-01-02',
            'author' => 'John Doe',
        ]);

        $this->assertEquals('Article', $schema['@type']);
        $this->assertEquals('Test Article', $schema['headline']);
        $this->assertEquals('John Doe', $schema['author']['name']);
    }

    // ==================== AnalyticsService ====================

    public function test_analytics_ga4_empty_when_no_setting(): void
    {
        $result = AnalyticsService::ga4Script();

        $this->assertEmpty($result);
    }

    public function test_analytics_ga4_non_empty_when_setting_supplied(): void
    {
        SettingsService::set('google_analytics_id', 'G-ABC1234567');

        $result = AnalyticsService::ga4Script();

        $this->assertNotEmpty($result);
        $this->assertStringContainsString('G-ABC1234567', $result);
        $this->assertStringContainsString('gtag', $result);
    }

    public function test_analytics_gtm_empty_when_no_setting(): void
    {
        $result = AnalyticsService::gtmScript();

        $this->assertEmpty($result);
    }

    public function test_analytics_gtm_non_empty_when_setting_supplied(): void
    {
        SettingsService::set('google_tag_manager_id', 'GTM-TEST123');

        $result = AnalyticsService::gtmScript();

        $this->assertNotEmpty($result);
        $this->assertStringContainsString('GTM-TEST123', $result);
    }

    public function test_analytics_meta_pixel_empty_when_no_setting(): void
    {
        $result = AnalyticsService::metaPixelScript();

        $this->assertEmpty($result);
    }

    public function test_analytics_meta_pixel_non_empty_when_setting_supplied(): void
    {
        SettingsService::set('meta_pixel_id', '1234567890');

        $result = AnalyticsService::metaPixelScript();

        $this->assertNotEmpty($result);
        $this->assertStringContainsString('1234567890', $result);
        $this->assertStringContainsString('fbq', $result);
    }

    public function test_analytics_clarity_empty_when_no_setting(): void
    {
        $result = AnalyticsService::clarityScript();

        $this->assertEmpty($result);
    }

    public function test_analytics_clarity_non_empty_when_setting_supplied(): void
    {
        SettingsService::set('microsoft_clarity_id', 'clarityABC');

        $result = AnalyticsService::clarityScript();

        $this->assertNotEmpty($result);
        $this->assertStringContainsString('clarityABC', $result);
        $this->assertStringContainsString('clarity.ms', $result);
    }

    public function test_analytics_search_console_empty_when_no_setting(): void
    {
        $result = AnalyticsService::searchConsoleMeta();

        $this->assertEmpty($result);
    }

    public function test_analytics_search_console_non_empty_when_setting_supplied(): void
    {
        SettingsService::set('search_console_token', 'tokenXYZ');

        $result = AnalyticsService::searchConsoleMeta();

        $this->assertNotEmpty($result);
        $this->assertStringContainsString('google-site-verification', $result);
        $this->assertStringContainsString('tokenXYZ', $result);
    }

    public function test_analytics_has_any_false_when_none_set(): void
    {
        $this->assertFalse(AnalyticsService::hasAny());
    }

    public function test_analytics_has_any_true_when_one_set(): void
    {
        SettingsService::set('google_analytics_id', 'G-XXX');

        $this->assertTrue(AnalyticsService::hasAny());
    }
}
