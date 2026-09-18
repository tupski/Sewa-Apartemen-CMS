<?php

namespace Tests\Feature;

use App\Models\Place;
use App\Models\PlaceCategory;
use App\Models\Property;
use App\Models\PropertyPlace;
use App\Models\Role;
use App\Models\User;
use App\Services\MapSettingsService;
use App\Services\SettingsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Phase 4 — map settings (style catalogue, theme resolution, admin settings
 * group) and the property-page map payload.
 *
 * Pins: style keys are stable identifiers with lang-file labels, theme mode
 * resolution (follow/light/dark), the admin Map settings group persists valid
 * keys and rejects unknown ones, and the property page renders the resolved
 * style + DB-managed marker config without any provider call on render.
 */
class MapSettingsTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        SettingsService::clearCache();
    }

    protected function authenticate(): void
    {
        $role = Role::updateOrCreate(['slug' => 'super-admin'], ['name' => 'Super Admin']);
        $this->user->roles()->syncWithoutDetaching([$role->id => ['model_type' => User::class]]);

        $this->actingAs($this->user);
    }

    /* ===================================================================
     | Style catalogue + resolution
     * =================================================================== */

    public function test_all_six_requested_styles_exist_with_stable_keys(): void
    {
        foreach (['osm-openmaptiles', 'maptiler-basic', 'osm-bright', 'positron', 'dark-matter', 'fiord-color'] as $key) {
            $this->assertArrayHasKey($key, MapSettingsService::STYLES);
        }

        // Dark styles are flagged so theme resolution can pick them.
        $this->assertTrue(MapSettingsService::STYLES['dark-matter']['dark']);
        $this->assertTrue(MapSettingsService::STYLES['fiord-color']['dark']);
        $this->assertFalse(MapSettingsService::STYLES['osm-bright']['dark']);
        $this->assertFalse(MapSettingsService::STYLES['positron']['dark']);
    }

    public function test_theme_mode_follow_resolves_by_theme(): void
    {
        SettingsService::set('map_theme_mode', 'follow', 'map');
        SettingsService::set('map_style_light', 'positron', 'map');
        SettingsService::set('map_style_dark', 'dark-matter', 'map');

        $this->assertSame('positron', MapSettingsService::resolveStyle(false));
        $this->assertSame('dark-matter', MapSettingsService::resolveStyle(true));
    }

    public function test_requested_style_names_map_to_verified_geoapify_style_ids(): void
    {
        // "MapTiler Basic" and "Fiord Color" are NOT Geoapify style IDs (verified
        // against the published catalogue) — the stable internal keys must map
        // to verified-servable Geoapify equivalents, never 404ing URLs.
        $this->assertStringContainsString(
            '/tile/klokantech-basic/',
            (string) MapSettingsService::STYLES['maptiler-basic']['url']
        );
        $this->assertStringContainsString(
            '/tile/dark-matter-dark-grey/',
            (string) MapSettingsService::STYLES['fiord-color']['url']
        );

        // Every Geoapify-hosted style in the catalogue must use a style ID
        // from the verified published list.
        $verified = [
            'osm-carto', 'osm-bright', 'osm-bright-grey', 'osm-bright-smooth',
            'klokantech-basic', 'osm-liberty', 'maptiler-3d', 'toner', 'toner-grey',
            'positron', 'positron-blue', 'positron-red', 'dark-matter',
            'dark-matter-brown', 'dark-matter-dark-grey', 'dark-matter-dark-purple',
            'dark-matter-purple-roads', 'dark-matter-yellow-roads',
        ];
        foreach (MapSettingsService::STYLES as $style) {
            if (! str_contains($style['url'], 'maps.geoapify.com')) {
                continue;
            }

            $path = parse_url($style['url'], PHP_URL_PATH);
            $this->assertMatchesRegularExpression('#^/v1/tile/([a-z0-9-]+)/#', (string) $path);
            preg_match('#^/v1/tile/([a-z0-9-]+)/#', (string) $path, $m);
            $this->assertContains($m[1], $verified, "Style URL uses unverified Geoapify ID: {$m[1]}");
        }
    }

    public function test_theme_mode_light_pins_the_light_style(): void
    {
        SettingsService::set('map_theme_mode', 'light', 'map');
        SettingsService::set('map_style_light', 'positron', 'map');

        $this->assertSame('positron', MapSettingsService::resolveStyle(false));
        $this->assertSame('positron', MapSettingsService::resolveStyle(true));
    }

    public function test_theme_mode_dark_pins_the_dark_style(): void
    {
        SettingsService::set('map_theme_mode', 'dark', 'map');
        SettingsService::set('map_style_dark', 'fiord-color', 'map');

        $this->assertSame('fiord-color', MapSettingsService::resolveStyle(false));
        $this->assertSame('fiord-color', MapSettingsService::resolveStyle(true));
    }

    public function test_invalid_stored_style_falls_back_to_defaults(): void
    {
        SettingsService::set('map_style_light', 'not-a-style', 'map');
        SettingsService::set('map_style_dark', 'osm-bright', 'map'); // light style for the dark slot

        $this->assertSame(MapSettingsService::DEFAULT_STYLE, MapSettingsService::lightStyle());
        $this->assertSame('dark-matter', MapSettingsService::darkStyle());
    }

    public function test_style_url_appends_the_map_key_and_fails_safe_without_one(): void
    {
        // No map key configured: Geoapify styles cannot resolve → null (JS falls
        // back to OSM standard tiles instead of an invented URL).
        config()->set('services.geoapify.key', '');
        config()->set('services.geoapify.map_key', '');
        SettingsService::clearCache();

        $this->assertNull(MapSettingsService::styleUrl('osm-bright'));

        // Keyless OSM standard always resolves.
        $this->assertSame('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', MapSettingsService::styleUrl('osm-standard'));

        // With a key the URL is complete and the key is appended (never leaked
        // elsewhere — this is the browser-facing map key by design, referrer-restricted).
        SettingsService::set('geoapify_map_key', 'referrer-restricted-key', 'integrations');
        SettingsService::clearCache();
        $this->assertSame(
            'https://maps.geoapify.com/v1/tile/osm-bright/{z}/{x}/{y}.png?apiKey=referrer-restricted-key',
            MapSettingsService::styleUrl('osm-bright')
        );
    }

    /* ===================================================================
     | Admin settings group
     * =================================================================== */

    public function test_admin_can_save_map_settings(): void
    {
        $this->authenticate();

        $response = $this->post(route('admin.settings.update', ['group' => 'map']), [
            'map_theme_mode' => 'follow',
            'map_style_light' => 'positron',
            'map_style_dark' => 'fiord-color',
        ]);

        $response->assertRedirect();

        SettingsService::clearCache();
        $this->assertSame('positron', SettingsService::get('map_style_light'));
        $this->assertSame('fiord-color', SettingsService::get('map_style_dark'));
        $this->assertSame('follow', SettingsService::get('map_theme_mode'));
    }

    public function test_map_settings_reject_unknown_style_keys(): void
    {
        $this->authenticate();

        $response = $this->post(route('admin.settings.update', ['group' => 'map']), [
            'map_theme_mode' => 'follow',
            'map_style_light' => 'totally-made-up-style',
            'map_style_dark' => 'dark-matter',
        ]);

        $response->assertSessionHasErrors(['map_style_light']);
        $this->assertNull(SettingsService::get('map_style_light'));
    }

    public function test_map_settings_cannot_be_changed_by_a_non_admin(): void
    {
        // Guest → redirected to login, nothing stored.
        $this->post(route('admin.settings.update', ['group' => 'map']), [
            'map_theme_mode' => 'dark',
            'map_style_light' => 'positron',
            'map_style_dark' => 'dark-matter',
        ])->assertRedirect(route('login'));

        // Authenticated non-admin → forbidden.
        $this->actingAs($this->user)
            ->post(route('admin.settings.update', ['group' => 'map']), [
                'map_theme_mode' => 'dark',
                'map_style_light' => 'positron',
                'map_style_dark' => 'dark-matter',
            ])
            ->assertForbidden();

        $this->assertNull(SettingsService::get('map_theme_mode'));
    }

    public function test_map_settings_page_renders_all_style_labels(): void
    {
        $this->authenticate();

        $response = $this->get(route('admin.settings.index', ['group' => 'map']));

        $response->assertOk();
        $response->assertSee(__('map_style.osm-bright'), false);
        $response->assertSee(__('map_style.positron'), false);
        $response->assertSee(__('map_style.dark-matter'), false);
        $response->assertSee(__('map_style.fiord-color'), false);
        $response->assertSee(__('map_style.osm-openmaptiles'), false);
        $response->assertSee(__('map_style.maptiler-basic'), false);
    }

    /**
     * The map group renders its own partial inside the shared settings <form>,
     * so the submit control lives in the partial. It shipped without one — the
     * page rendered fine and the update endpoint worked, so only a test that
     * asserts the BUTTON is present catches it.
     */
    public function test_map_settings_page_has_a_save_button(): void
    {
        $this->authenticate();

        $html = $this->get(route('admin.settings.index', ['group' => 'map']))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('Save Settings', $html);
        $this->assertStringContainsString(
            'type="submit"',
            $this->settingsFormFragment($html),
            'The map settings form has no submit button, so it cannot be saved from the UI.'
        );
    }

    /**
     * Every settings group that persists keys must expose a submit control;
     * a group whose partial forgets one is unsavable from the UI.
     */
    public function test_every_savable_settings_group_renders_a_save_button(): void
    {
        $this->authenticate();

        foreach (['general', 'homepage', 'footer', 'theme', 'seo', 'integrations', 'pricing', 'mail', 'email_templates', 'captcha', 'currency_api', 'map'] as $group) {
            $html = $this->get(route('admin.settings.index', ['group' => $group]))
                ->assertOk()
                ->getContent();

            $this->assertStringContainsString(
                'type="submit"',
                $this->settingsFormFragment($html),
                "The '{$group}' settings group renders no submit button, so it cannot be saved from the UI."
            );
        }
    }

    /**
     * Return the markup of the settings <form> only.
     *
     * Neither the update URL nor the first `type="submit"` on the page is a
     * usable anchor: the index and update routes share the same URL (only the
     * HTTP method differs), so the sidebar link matches first, and the admin
     * layout renders other forms (logout) whose buttons would satisfy a
     * page-wide search. The form carries `data-warn-unsaved`, which is unique.
     */
    private function settingsFormFragment(string $html): string
    {
        $anchor = strpos($html, 'data-warn-unsaved');
        $this->assertNotFalse($anchor, 'The settings form (data-warn-unsaved) was not found.');

        $open = strrpos(substr($html, 0, $anchor), '<form');
        $close = strpos($html, '</form>', $anchor);
        $this->assertNotFalse($open, 'The settings <form> opening tag was not found.');
        $this->assertNotFalse($close, 'The settings <form> closing tag was not found.');

        return substr($html, $open, $close - $open);
    }

    /* ===================================================================
     | Property page map payload
     * =================================================================== */

    public function test_property_page_renders_resolved_style_and_category_marker_config(): void
    {
        $this->authenticate();

        SettingsService::set('map_theme_mode', 'follow', 'map');
        SettingsService::set('map_style_light', 'positron', 'map');
        SettingsService::set('geoapify_map_key', 'referrer-restricted-key', 'integrations');
        SettingsService::clearCache();

        $category = PlaceCategory::updateOrCreate(
            ['slug' => 'healthcare.hospital'],
            ['name_id' => 'Rumah Sakit',
                'name_en' => 'Hospital',
                'icon' => 'fa-solid fa-hospital',
                'color' => '#ef4444',
                'is_active' => true]);

        $property = Property::factory()->create([
            'status' => 'published',
            'latitude' => -6.2,
            'longitude' => 106.8,
        ]);

        $place = Place::create([
            'geoapify_place_id' => 'gp-map-1',
            'name' => 'RS Sehat',
            'category' => 'healthcare.hospital',
            'lat' => -6.205,
            'lng' => 106.805,
            'address' => 'Jl. Contoh No. 1',
            'fetched_at' => now(),
        ]);

        PropertyPlace::create([
            'property_id' => $property->id,
            'place_id' => $place->id,
            'source' => 'geoapify',
            'distance_m' => 700,
            'show_on_frontend' => true,
            'custom_name' => 'RS Dekat',
        ]);

        Http::preventStrayRequests();

        $response = $this->get(route('properties.public.show', $property));

        $response->assertOk();
        // Resolved light style (positron) with the map key (the browser-facing,
        // referrer-restricted key) and the DB-managed marker config.
        $response->assertSee('positron', false);
        $response->assertSee('apiKey=referrer-restricted-key', false);
        // No standalone key field, and no unused raw provider payload: the map
        // key only ever appears embedded in a style URL.
        $response->assertDontSee('"mapKey"', false);
        $response->assertDontSee('"provider"', false);
        $response->assertSee('Rumah Sakit', false);
        $response->assertSee('fa-solid fa-hospital', false);
        $response->assertSee('#ef4444', false);
        $response->assertSee('RS Dekat', false);
        // The full Places API key must never appear anywhere.
        $response->assertDontSee('super-secret-places-key', false);

        Http::assertNothingSent();
    }

    public function test_property_page_map_renders_without_pois(): void
    {
        Http::preventStrayRequests();

        $property = Property::factory()->create([
            'status' => 'published',
            'latitude' => -6.2,
            'longitude' => 106.8,
        ]);

        $this->get(route('properties.public.show', $property))
            ->assertOk()
            ->assertSee('property-map', false);

        Http::assertNothingSent();
    }

    /**
     * The client must be able to resolve the tile URL from the LIVE theme.
     *
     * The server resolves `styleUrl` from the `theme` cookie, which is stale
     * right after an in-page toggle (the toggle flips the `dark` class without
     * a reload). A Turbo navigation reuses the dark document, so the client
     * needs the mode + both variants to pick the right tiles itself.
     */
    public function test_property_page_map_payload_exposes_the_theme_mode(): void
    {
        Http::preventStrayRequests();
        Http::fake();

        SettingsService::set('map_theme_mode', 'follow', 'map');
        SettingsService::clearCache();

        $property = Property::factory()->create([
            'status' => 'published',
            'latitude' => -6.2,
            'longitude' => 106.8,
        ]);

        $payload = $this->extractMapData(
            $this->get(route('properties.public.show', $property->slug))->getContent()
        );

        $this->assertSame('follow', $payload['themeMode']);
        // Both variants are always present, so the client can swap without a
        // round-trip.
        $this->assertArrayHasKey('styleUrlLight', $payload);
        $this->assertArrayHasKey('styleUrlDark', $payload);
    }

    public function test_pinned_map_theme_mode_is_reported_to_the_client(): void
    {
        Http::preventStrayRequests();
        Http::fake();

        SettingsService::set('map_theme_mode', 'dark', 'map');
        SettingsService::clearCache();

        $property = Property::factory()->create([
            'status' => 'published',
            'latitude' => -6.2,
            'longitude' => 106.8,
        ]);

        $payload = $this->extractMapData(
            $this->get(route('properties.public.show', $property->slug))->getContent()
        );

        // A pinned mode must reach the client, so the client does NOT follow
        // the site theme (that was the second half of the bug).
        $this->assertSame('dark', $payload['themeMode']);
    }

    /**
     * Extract the #map-data JSON payload from a rendered property page.
     *
     * @return array<string, mixed>
     */
    private function extractMapData(string $html): array
    {
        $matched = preg_match(
            '#<script type="application/json" id="map-data">(.*?)</script>#s',
            $html,
            $matches
        );
        $this->assertSame(1, $matched, 'The #map-data JSON block was not rendered.');

        $decoded = json_decode($matches[1], true);
        $this->assertIsArray($decoded, 'The #map-data payload is not valid JSON.');

        return $decoded;
    }
}
