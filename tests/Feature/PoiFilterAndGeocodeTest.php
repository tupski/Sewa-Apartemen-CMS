<?php

namespace Tests\Feature;

use App\Models\Place;
use App\Models\PlaceCategory;
use App\Models\Property;
use App\Models\PropertyPlace;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Phase 3 completion — POI table search/filter and the geocoding proxy.
 *
 * Pins: filtering is pure client-side DB data (zero HTTP requests), the
 * property geocoding search flows through the Laravel backend (never the
 * browser → provider), malformed provider coordinates are dropped, provider
 * failures are handled, and no API key ever reaches the frontend.
 */
class PoiFilterAndGeocodeTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
    }

    protected function authenticate(): void
    {
        $role = Role::updateOrCreate(['slug' => 'super-admin'], ['name' => 'Super Admin']);
        $this->user->roles()->syncWithoutDetaching([$role->id => ['model_type' => User::class]]);

        $this->actingAs($this->user);
    }

    /* ===================================================================
     | POI filtering — edit screen renders DB data, no external calls
     * =================================================================== */

    public function test_edit_screen_loads_all_poi_rows_from_the_database_without_external_calls(): void
    {
        $this->authenticate();
        Http::preventStrayRequests();

        PlaceCategory::create(['slug' => 'healthcare.hospital', 'name_id' => 'Rumah Sakit', 'name_en' => 'Hospital', 'is_active' => true]);
        PlaceCategory::create(['slug' => 'catering.cafe', 'name_id' => 'Kafe', 'name_en' => 'Cafe', 'is_active' => true]);

        $property = Property::factory()->create(['status' => 'published', 'latitude' => -6.2, 'longitude' => 106.8]);

        foreach ([
            ['name' => 'RS Sehat', 'category' => 'healthcare.hospital', 'custom' => 'RS Dekat Apartemen', 'visible' => true],
            ['name' => 'Kopi Kiosk', 'category' => 'catering.cafe', 'custom' => null, 'visible' => false],
        ] as $data) {
            $place = Place::create([
                'geoapify_place_id' => 'gp-'.uniqid(),
                'name' => $data['name'],
                'category' => $data['category'],
                'lat' => -6.205,
                'lng' => 106.805,
                'fetched_at' => now(),
            ]);

            PropertyPlace::create([
                'property_id' => $property->id,
                'place_id' => $place->id,
                'source' => 'geoapify',
                'distance_m' => 700,
                'show_on_frontend' => $data['visible'],
                'custom_name' => $data['custom'],
            ]);
        }

        $response = $this->get(route('admin.properties.edit', $property));

        $response->assertOk();
        $response->assertSee('RS Sehat', false);
        $response->assertSee('Kopi Kiosk', false);
        $response->assertSee('RS Dekat Apartemen', false);
        $response->assertSee('Rumah Sakit', false);
        $response->assertSee('Kafe', false);

        // Zero outbound HTTP: the render path never talks to any provider.
        Http::assertNothingSent();
    }

    /* ===================================================================
     | Geocoding proxy
     * =================================================================== */

    public function test_geocode_search_returns_normalized_results(): void
    {
        $this->authenticate();

        Http::preventStrayRequests();
        Http::fake([
            'nominatim.openstreetmap.org/*' => Http::response([
                ['place_id' => 1, 'display_name' => 'Bintaro, Tangerang Selatan', 'lat' => '-6.271', 'lon' => '106.713'],
                ['place_id' => 2, 'display_name' => 'Bintaro Jaya', 'lat' => 'abc', 'lon' => '106.7'], // malformed → dropped
                ['place_id' => 3, 'display_name' => 'Kargo', 'lat' => '999', 'lon' => '106.7'],        // out of bounds → dropped
            ], 200),
        ]);

        $response = $this->getJson(route('admin.geocode.search', ['q' => 'Bintaro']));

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);

        $results = $response->json('results');
        $this->assertCount(1, $results);
        $this->assertSame('Bintaro, Tangerang Selatan', $results[0]['display_name']);
        $this->assertSame(-6.271, $results[0]['lat']);
        $this->assertSame(106.713, $results[0]['lng']);
    }

    public function test_geocode_results_are_cached_across_repeat_requests(): void
    {
        $this->authenticate();

        Http::preventStrayRequests();
        Http::fake([
            'nominatim.openstreetmap.org/*' => Http::response([
                ['place_id' => 1, 'display_name' => 'Bintaro', 'lat' => '-6.271', 'lon' => '106.713'],
            ], 200),
        ]);

        $this->getJson(route('admin.geocode.search', ['q' => 'Bintaro']))->assertStatus(200);
        $this->getJson(route('admin.geocode.search', ['q' => 'Bintaro']))->assertStatus(200);

        Http::assertSentCount(1); // second hit came from the cache
    }

    public function test_geocode_search_validates_the_query(): void
    {
        $this->authenticate();

        Http::preventStrayRequests();

        $this->getJson(route('admin.geocode.search', ['q' => '']))->assertStatus(422);
        $this->getJson(route('admin.geocode.search', ['q' => 'a']))->assertStatus(422);
        $this->getJson(route('admin.geocode.search', ['q' => str_repeat('x', 256)]))->assertStatus(422);

        Http::assertNothingSent();
    }

    public function test_geocode_provider_failure_is_handled_without_leaking_details(): void
    {
        $this->authenticate();

        Http::preventStrayRequests();
        Http::fake([
            'nominatim.openstreetmap.org/*' => Http::response('server error', 500),
        ]);

        $response = $this->getJson(route('admin.geocode.search', ['q' => 'Bintaro']));

        $response->assertStatus(502);
        $response->assertJsonMissing(['apiKey']);
        $this->assertStringNotContainsString('server error', $response->getContent());
    }

    public function test_geocode_search_requires_an_authenticated_admin(): void
    {
        Http::preventStrayRequests();

        // Guest → redirect to login (no provider call).
        $this->get(route('admin.geocode.search', ['q' => 'Bintaro']))
            ->assertRedirect(route('login'));

        // Non-admin → forbidden.
        $this->actingAs($this->user)
            ->getJson(route('admin.geocode.search', ['q' => 'Bintaro']))
            ->assertForbidden();

        Http::assertNothingSent();
    }

    public function test_no_api_key_appears_in_the_edit_page_output(): void
    {
        $this->authenticate();

        config()->set('services.geoapify.key', 'super-secret-test-key');
        config()->set('services.geoapify.map_key', 'super-secret-test-key');

        $property = Property::factory()->create(['status' => 'published', 'latitude' => -6.2, 'longitude' => 106.8]);

        $this->get(route('admin.properties.edit', $property))
            ->assertOk()
            ->assertDontSee('super-secret-test-key', false);

        $this->getJson(route('admin.geocode.search', ['q' => 'Bintaro']))
            ->assertDontSee('super-secret-test-key', false);
    }
}
