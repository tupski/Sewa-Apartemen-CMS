<?php

namespace Tests\Feature;

use App\Jobs\FetchNearbyPlacesJob;
use App\Models\Place;
use App\Models\PlaceCategory;
use App\Models\Property;
use App\Models\PropertyPlace;
use App\Models\Role;
use App\Models\User;
use App\Services\GeoapifyService;
use App\Services\SettingsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

/**
 * Geoapify persistent-POI pipeline.
 *
 * Covers GeoapifyService normalization/error handling, the Places + Route Matrix
 * pipeline in FetchNearbyPlacesJob (persistence, walking-time filtering,
 * idempotency, caching), the admin sync/list endpoints, the public property page
 * rendering (including the "zero outbound HTTP on render" guarantee), and the
 * Place/PropertyPlace model behaviour.
 *
 * NO test in this file performs a real network call: every test that can reach
 * Geoapify installs Http::fake() + Http::preventStrayRequests() first, and the
 * API key used everywhere is the dummy string 'test-key'.
 *
 * Factory decision: there are no Place/PropertyPlace factories and those models
 * do not use HasFactory, so rows are seeded with Model::create() through the
 * private helpers below — consistently, with no new factories added.
 */
class GeoapifyNearbyPlacesTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();

        // SettingsService keeps a process-wide static cache; clear it so a key
        // stored by another test can never leak into this one.
        SettingsService::clearCache();

        // Dummy Geoapify config for every test. Never a real key.
        config()->set('services.geoapify.key', 'test-key');
        config()->set('services.geoapify.map_key', 'test-map-key');
        config()->set('services.geoapify.radius', 2000);
        config()->set('services.geoapify.max_results', 20);

        $this->seedCategories();
    }

    /**
     * Seed a small, deterministic category fixture (instead of the full 20)
     * so pipeline tests issue a predictable, low number of Places requests.
     *
     * Requests per sync: 4 Places (one per category) + 3 Route Matrix
     * (walk / drive / motorcycle) = 7.
     */
    protected function seedCategories(): void
    {
        foreach ([
            ['slug' => 'healthcare.hospital', 'name_id' => 'Rumah Sakit', 'name_en' => 'Hospital', 'icon' => 'fa-solid fa-hospital', 'color' => '#ef4444', 'sort_order' => 0],
            ['slug' => 'commercial.shopping_mall', 'name_id' => 'Mal', 'name_en' => 'Shopping Mall', 'icon' => 'fa-solid fa-bag-shopping', 'color' => '#f59e0b', 'sort_order' => 1],
            ['slug' => 'public_transport', 'name_id' => 'Transportasi Umum', 'name_en' => 'Public Transport', 'icon' => 'fa-solid fa-train-subway', 'color' => '#7c3aed', 'sort_order' => 2],
            ['slug' => 'public_transport.train', 'name_id' => 'Stasiun Kereta', 'name_en' => 'Train Station', 'icon' => 'fa-solid fa-train', 'color' => '#7c3aed', 'sort_order' => 3],
        ] as $category) {
            PlaceCategory::firstOrCreate(['slug' => $category['slug']], $category);
        }
    }

    /* ===================================================================
     | Helpers
     * =================================================================== */

    /**
     * Grant the super-admin role and log in, matching the pattern used by
     * CrudTest / PropertyNearbyPlacesTest.
     */
    protected function authenticate(): void
    {
        $role = Role::updateOrCreate(['slug' => 'super-admin'], ['name' => 'Super Admin']);
        $this->user->roles()->syncWithoutDetaching([$role->id => ['model_type' => User::class]]);

        $this->actingAs($this->user);
    }

    /**
     * Build a single realistic Geoapify GeoJSON feature.
     *
     * @param  array<string, mixed>  $properties  Overrides merged into `properties`.
     * @param  array<int, float>|null  $coordinates  GeoJSON [lng, lat] pair.
     * @return array<string, mixed>
     */
    private function poiFeature(array $properties = [], ?array $coordinates = null): array
    {
        $props = array_merge([
            'place_id' => 'gp-hospital-1',
            'name' => 'RS Sehat Sentosa',
            'categories' => ['healthcare.hospital'],
            'formatted' => 'Jl. Sudirman No. 1, Jakarta',
            'website' => 'https://rs.example.test',
            'contact' => ['phone' => '+622****4567'],
        ], $properties);

        // GeoJSON order is [longitude, latitude] — deliberately asymmetric so the
        // un-swapping inside GeoapifyService is actually exercised.
        return [
            'type' => 'Feature',
            'properties' => $props,
            'geometry' => [
                'type' => 'Point',
                'coordinates' => $coordinates ?? [106.81, -6.21],
            ],
        ];
    }

    /**
     * Install an Http fake covering BOTH Geoapify endpoints used by the pipeline:
     * the Places search and the Route Matrix walking-time calculation.
     *
     * @param  array<int, array<string, mixed>>  $features  Places API features
     * @param  array<string, int|null>  $walkSeconds  place_id => routed walking seconds.
     *                                                Defaults to 300s (5 min, inside the
     *                                                10-minute budget) for every POI.
     */
    private function fakeGeoapify(array $features, array $walkSeconds = []): void
    {
        Http::preventStrayRequests();

        // place_id => GeoJSON [lng, lat], so the matrix fake can resolve the
        // targets it is asked about back to their place ids.
        $coordinates = [];
        foreach ($features as $feature) {
            $coordinates[$feature['properties']['place_id']] = $feature['geometry']['coordinates'];
        }

        Http::fake([
            'api.geoapify.com/v2/places*' => Http::response([
                'type' => 'FeatureCollection',
                'features' => $features,
            ], 200),

            'api.geoapify.com/v1/routematrix*' => function ($request) use ($coordinates, $walkSeconds) {
                $targets = $request->data()['targets'] ?? [];
                $row = [];

                foreach ($targets as $index => $target) {
                    $placeId = null;
                    [$lng, $lat] = $target['location'];

                    foreach ($coordinates as $id => [$poiLng, $poiLat]) {
                        if (abs($poiLng - $lng) < 1e-9 && abs($poiLat - $lat) < 1e-9) {
                            $placeId = $id;
                            break;
                        }
                    }

                    $seconds = $placeId === null
                        ? null
                        : ($walkSeconds[$placeId] ?? 300);

                    $row[] = [
                        'distance' => $seconds === null ? null : (int) round($seconds * 1.3),
                        'time' => $seconds,
                        'source_index' => 0,
                        'target_index' => $index,
                    ];
                }

                return Http::response(['sources_to_targets' => [$row]], 200);
            },
        ]);
    }

    /**
     * A published property with coordinates in Jakarta.
     *
     * @param  array<string, mixed>  $attributes
     */
    private function propertyWithCoords(array $attributes = []): Property
    {
        return Property::factory()->create(array_merge([
            'status' => 'published',
            'latitude' => -6.2,
            'longitude' => 106.8,
        ], $attributes));
    }

    /**
     * Seed a Place plus its PropertyPlace pivot row directly (no API involved).
     *
     * @param  array<string, mixed>  $placeAttributes
     * @param  array<string, mixed>  $pivotAttributes
     */
    private function seedPlace(
        Property $property,
        array $placeAttributes = [],
        array $pivotAttributes = []
    ): PropertyPlace {
        static $seq = 0;
        $seq++;

        $place = Place::create(array_merge([
            'geoapify_place_id' => 'gp-seeded-'.$seq,
            'name' => 'Seeded Place',
            'category' => GeoapifyService::GROUP_HOSPITAL,
            'lat' => -6.205,
            'lng' => 106.805,
            'address' => 'Jl. Seeded No. 9',
            'website' => null,
            'phone' => null,
            'raw_category' => 'healthcare.hospital',
            'fetched_at' => now(),
        ], $placeAttributes));

        return PropertyPlace::create(array_merge([
            'property_id' => $property->id,
            'place_id' => $place->id,
            'source' => 'geoapify',
            'distance_m' => 800,
            'sort_order' => 0,
        ], $pivotAttributes));
    }

    /**
     * Pull the #map-data JSON payload out of the rendered page.
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

    /* ===================================================================
     | Group 1 — GeoapifyService
     * =================================================================== */

    public function test_service_returns_normalized_pois_with_unswapped_coordinates(): void
    {
        $this->fakeGeoapify([$this->poiFeature()]);

        $pois = (new GeoapifyService)->searchGroup(GeoapifyService::GROUP_HOSPITAL, -6.2, 106.8);

        $this->assertCount(1, $pois);

        $poi = $pois[0];

        $this->assertSame([
            'geoapify_place_id',
            'name',
            'raw_category',
            'category',
            'lat',
            'lng',
            'address',
            'website',
            'phone',
        ], array_keys($poi));

        $this->assertSame('gp-hospital-1', $poi['geoapify_place_id']);
        $this->assertSame('RS Sehat Sentosa', $poi['name']);
        $this->assertSame('healthcare.hospital', $poi['raw_category']);
        $this->assertSame('Hospital/Health', $poi['category']);
        $this->assertSame('Jl. Sudirman No. 1, Jakarta', $poi['address']);
        $this->assertSame('https://rs.example.test', $poi['website']);
        $this->assertSame('+622****4567', $poi['phone']);

        // GeoJSON gave [106.81, -6.21]; lat/lng must be un-swapped.
        $this->assertSame(-6.21, $poi['lat']);
        $this->assertSame(106.81, $poi['lng']);
    }

    public function test_service_skips_features_without_a_name(): void
    {
        $this->fakeGeoapify([
            // `name` missing entirely.
            $this->poiFeature(['place_id' => 'gp-no-name', 'name' => null]),
            // `name` present but empty.
            $this->poiFeature(['place_id' => 'gp-empty-name', 'name' => '']),
            $this->poiFeature(['place_id' => 'gp-named', 'name' => 'RS Bunda']),
        ]);

        $pois = (new GeoapifyService)->searchGroup(GeoapifyService::GROUP_HOSPITAL, -6.2, 106.8);

        $this->assertCount(1, $pois);
        $this->assertSame('RS Bunda', $pois[0]['name']);
    }

    public function test_service_returns_empty_array_when_response_has_no_features_key(): void
    {
        Http::preventStrayRequests();
        Http::fake([
            'api.geoapify.com/*' => Http::response(['type' => 'FeatureCollection'], 200),
        ]);

        $this->assertSame(
            [],
            (new GeoapifyService)->searchGroup(GeoapifyService::GROUP_HOSPITAL, -6.2, 106.8)
        );
    }

    public function test_service_throws_on_401_and_does_not_retry(): void
    {
        Http::preventStrayRequests();
        Http::fake([
            'api.geoapify.com/*' => Http::response(['message' => 'Invalid apiKey'], 401),
        ]);

        try {
            (new GeoapifyService)->searchGroup(GeoapifyService::GROUP_HOSPITAL, -6.2, 106.8);
            $this->fail('Expected a RuntimeException for HTTP 401.');
        } catch (\RuntimeException $e) {
            $this->assertSame('Geoapify API key invalid or quota exceeded', $e->getMessage());
        }

        // 4xx must not be retried.
        Http::assertSentCount(1);
    }

    public function test_service_throws_on_invalid_json_body(): void
    {
        Http::preventStrayRequests();
        Http::fake([
            'api.geoapify.com/*' => Http::response('<<not json at all>>', 200),
        ]);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Geoapify returned invalid JSON');

        (new GeoapifyService)->searchGroup(GeoapifyService::GROUP_HOSPITAL, -6.2, 106.8);
    }

    public function test_service_constructor_throws_when_api_key_is_missing(): void
    {
        config()->set('services.geoapify.key', '');

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Geoapify API key is not configured. Add it in Settings → Integrations.');

        new GeoapifyService;
    }

    public function test_service_request_includes_configured_radius_and_limit(): void
    {
        config()->set('services.geoapify.radius', 1500);
        config()->set('services.geoapify.max_results', 7);

        $this->fakeGeoapify([$this->poiFeature()]);

        (new GeoapifyService)->searchGroup(GeoapifyService::GROUP_HOSPITAL, -6.2, 106.8);

        Http::assertSent(function ($request) {
            $url = urldecode($request->url());

            // Geoapify's circle filter is "circle:{lng},{lat},{radius}".
            return str_contains($url, 'circle:106.8,-6.2,1500')
                && str_contains($url, 'limit=7')
                && str_contains($url, 'apiKey=test-key');
        });
    }

    public function test_service_maps_known_categories_and_excludes_unmapped_ones(): void
    {
        $this->fakeGeoapify([
            $this->poiFeature([
                'place_id' => 'gp-mall',
                'name' => 'Grand Mall',
                'categories' => ['commercial.shopping_mall'],
            ]),
            $this->poiFeature([
                'place_id' => 'gp-unmapped',
                'name' => 'Random Apartment Block',
                'categories' => ['building.residential'],
            ]),
        ]);

        $pois = (new GeoapifyService)->searchGroup(GeoapifyService::GROUP_SHOPPING, -6.2, 106.8);

        $this->assertCount(1, $pois);
        $this->assertSame('Grand Mall', $pois[0]['name']);
        $this->assertSame(GeoapifyService::GROUP_SHOPPING, $pois[0]['category']);

        // The mapped label must be a real NEARBY_CATEGORIES key.
        $this->assertArrayHasKey(GeoapifyService::GROUP_SHOPPING, Property::NEARBY_CATEGORIES);

        // The unmapped category is dropped entirely.
        $this->assertNotContains('Random Apartment Block', array_column($pois, 'name'));
    }

    public function test_service_group_categories_are_the_verified_geoapify_identifiers(): void
    {
        // Pins the category identifiers verified against Geoapify's supported
        // place categories, so an accidental edit cannot silently change which
        // POI types are synchronized.
        $this->assertSame([
            'commercial.shopping_mall',
            'commercial.department_store',
            'commercial.marketplace',
        ], GeoapifyService::POI_GROUPS[GeoapifyService::GROUP_SHOPPING]);

        $this->assertSame(['healthcare.hospital'], GeoapifyService::POI_GROUPS[GeoapifyService::GROUP_HOSPITAL]);

        $this->assertSame([
            'public_transport.train',
            'public_transport.subway',
            'public_transport.light_rail',
            'public_transport.monorail',
            'public_transport.tram',
            'public_transport.bus',
            'public_transport.ferry',
        ], GeoapifyService::POI_GROUPS[GeoapifyService::GROUP_TRANSPORT]);

        // Every group label is a canonical nearby-place category.
        foreach (array_keys(GeoapifyService::POI_GROUPS) as $group) {
            $this->assertArrayHasKey($group, Property::NEARBY_CATEGORIES);
        }
    }

    public function test_service_throws_for_an_unknown_group(): void
    {
        $this->fakeGeoapify([]);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Unknown POI group: NotAGroup');

        (new GeoapifyService)->searchGroup('NotAGroup', -6.2, 106.8);
    }

    /* ===================================================================
     | Group 2 — FetchNearbyPlacesJob
     * =================================================================== */

    public function test_job_persists_places_and_pivot_rows(): void
    {
        $property = $this->propertyWithCoords();

        $this->fakeGeoapify([
            $this->poiFeature(['place_id' => 'gp-1', 'name' => 'RS Sehat']),
            $this->poiFeature([
                'place_id' => 'gp-2',
                'name' => 'Grand Mall',
                'categories' => ['commercial.shopping_mall'],
            ], [106.815, -6.215]),
        ]);

        $result = (new FetchNearbyPlacesJob($property))->handle();

        $this->assertTrue($result['success']);
        $this->assertSame(2, $result['synced']);

        $this->assertDatabaseCount('places', 2);
        $this->assertDatabaseCount('property_places', 2);

        $this->assertDatabaseHas('places', [
            'geoapify_place_id' => 'gp-1',
            'name' => 'RS Sehat',
            'category' => 'healthcare.hospital',
        ]);
        $this->assertDatabaseHas('places', [
            'geoapify_place_id' => 'gp-2',
            'category' => 'commercial.shopping_mall',
        ]);

        foreach (PropertyPlace::all() as $pivot) {
            $this->assertSame($property->id, $pivot->property_id);
            // enum('manual','geoapify') round-trips correctly on SQLite.
            $this->assertSame('geoapify', $pivot->source);
            $this->assertNotNull($pivot->distance_m);
            $this->assertNotNull($pivot->walking_duration_s);
        }
    }

    public function test_job_is_idempotent_across_repeated_runs(): void
    {
        $property = $this->propertyWithCoords();

        $this->fakeGeoapify([
            $this->poiFeature(['place_id' => 'gp-1', 'name' => 'RS Sehat']),
            $this->poiFeature([
                'place_id' => 'gp-2',
                'name' => 'Stasiun Tengah',
                'categories' => ['public_transport.train'],
            ]),
        ]);

        (new FetchNearbyPlacesJob($property))->handle();

        // Clear the cache so the second run really re-fetches and re-upserts.
        Cache::forget("geoapify_places_{$property->id}");

        (new FetchNearbyPlacesJob($property))->handle();

        $this->assertDatabaseCount('places', 2);
        $this->assertDatabaseCount('property_places', 2);
    }

    public function test_job_deletes_stale_geoapify_pivot_rows(): void
    {
        $property = $this->propertyWithCoords();

        $stale = $this->seedPlace($property, [
            'geoapify_place_id' => 'gp-stale',
            'name' => 'Closed Down Mall',
            'category' => GeoapifyService::GROUP_SHOPPING,
        ]);

        $this->fakeGeoapify([
            $this->poiFeature(['place_id' => 'gp-fresh', 'name' => 'Still Open Hospital']),
        ]);

        (new FetchNearbyPlacesJob($property))->handle();

        $this->assertDatabaseMissing('property_places', ['id' => $stale->id]);
        $this->assertDatabaseHas('property_places', [
            'property_id' => $property->id,
            'source' => 'geoapify',
        ]);
        $this->assertSame(1, PropertyPlace::where('property_id', $property->id)->count());
    }

    public function test_job_preserves_manual_pivot_rows(): void
    {
        $property = $this->propertyWithCoords();

        $manual = $this->seedPlace(
            $property,
            ['geoapify_place_id' => null, 'name' => 'Hand Entered Landmark'],
            ['source' => 'manual']
        );

        $this->fakeGeoapify([
            $this->poiFeature(['place_id' => 'gp-fresh', 'name' => 'Fetched Hospital']),
        ]);

        (new FetchNearbyPlacesJob($property))->handle();

        $this->assertDatabaseHas('property_places', [
            'id' => $manual->id,
            'source' => 'manual',
        ]);
        $this->assertSame(2, PropertyPlace::where('property_id', $property->id)->count());
    }

    public function test_job_uses_the_cache_and_calls_the_api_only_once(): void
    {
        $property = $this->propertyWithCoords();

        $this->fakeGeoapify([
            $this->poiFeature(['place_id' => 'gp-1', 'name' => 'Cached Hospital']),
        ]);

        (new FetchNearbyPlacesJob($property))->handle();
        // No Cache::forget() — the second run must hit the 24h cached payload.
        (new FetchNearbyPlacesJob($property))->handle();

        // Cold cache: one Places request per category (4) + one Route Matrix
        // request per mode (walk / drive / motorcycle) = 7.
        Http::assertSentCount(7);
        $this->assertDatabaseCount('places', 1);
        $this->assertDatabaseCount('property_places', 1);
    }

    public function test_job_returns_early_when_property_has_no_coordinates(): void
    {
        $property = Property::factory()->create([
            'status' => 'published',
            'latitude' => null,
            'longitude' => null,
        ]);

        Http::preventStrayRequests();
        Http::fake();

        $result = (new FetchNearbyPlacesJob($property))->handle();

        $this->assertFalse($result['success']);
        $this->assertSame('missing_coordinates', $result['reason']);

        Http::assertNothingSent();
        $this->assertDatabaseCount('places', 0);
        $this->assertDatabaseCount('property_places', 0);
    }

    public function test_job_returns_early_when_api_key_is_not_configured(): void
    {
        config()->set('services.geoapify.key', '');

        $property = $this->propertyWithCoords();

        Http::preventStrayRequests();
        Http::fake();

        // The job guards before constructing the service, so nothing is thrown.
        $result = (new FetchNearbyPlacesJob($property))->handle();

        $this->assertFalse($result['success']);
        $this->assertSame('missing_api_key', $result['reason']);

        Http::assertNothingSent();
        $this->assertDatabaseCount('places', 0);
        $this->assertDatabaseCount('property_places', 0);
    }

    public function test_job_handles_persistent_api_failure_without_throwing(): void
    {
        $property = $this->propertyWithCoords();

        Http::preventStrayRequests();
        Http::fake([
            'api.geoapify.com/*' => Http::response('Internal Server Error', 500),
        ]);

        // handle() records the per-group failure and returns; it never throws.
        $result = (new FetchNearbyPlacesJob($property))->handle();

        $this->assertFalse($result['success']);
        $this->assertSame(0, $result['synced']);

        $this->assertDatabaseCount('places', 0);
        $this->assertDatabaseCount('property_places', 0);

        // A failure must never be cached.
        $this->assertNull(Cache::get("geoapify_places_{$property->id}"));
    }

    public function test_job_computes_a_plausible_distance(): void
    {
        // Property at lat -6.2000, POI at lat -6.2090 => roughly 1 km due south.
        $property = $this->propertyWithCoords();

        $this->fakeGeoapify([
            $this->poiFeature(
                ['place_id' => 'gp-1km', 'name' => 'One Km Hospital'],
                [106.8, -6.209]
            ),
        ]);

        (new FetchNearbyPlacesJob($property))->handle();

        $pivot = PropertyPlace::firstOrFail();

        $this->assertGreaterThan(0, $pivot->distance_m);
        $this->assertLessThan(5000, $pivot->distance_m);
        // Sanity band around the ~1 km separation, without pinning exact metres.
        $this->assertGreaterThan(500, $pivot->distance_m);
        $this->assertLessThan(1500, $pivot->distance_m);
    }

    /* ===================================================================
     | Group 3 — Admin endpoints
     * =================================================================== */

    public function test_admin_can_resync_nearby_places_and_the_job_is_dispatched(): void
    {
        Queue::fake();
        $this->authenticate();

        $property = $this->propertyWithCoords();

        $response = $this->post(route('admin.properties.resync-nearby-places', $property));

        $response->assertStatus(302);
        $response->assertSessionHas('success');

        Queue::assertPushed(FetchNearbyPlacesJob::class);
    }

    public function test_resync_without_coordinates_errors_and_does_not_dispatch(): void
    {
        Queue::fake();
        $this->authenticate();

        $property = Property::factory()->create([
            'status' => 'published',
            'latitude' => null,
            'longitude' => null,
        ]);

        $response = $this->post(route('admin.properties.resync-nearby-places', $property));

        $response->assertStatus(302);
        $response->assertSessionHas('error');

        Queue::assertNotPushed(FetchNearbyPlacesJob::class);
    }

    public function test_resync_without_api_key_errors_and_does_not_dispatch(): void
    {
        config()->set('services.geoapify.key', '');

        Queue::fake();
        $this->authenticate();

        $property = $this->propertyWithCoords();

        $response = $this->post(route('admin.properties.resync-nearby-places', $property));

        $response->assertStatus(302);
        $response->assertSessionHas('error');

        Queue::assertNotPushed(FetchNearbyPlacesJob::class);
    }

    public function test_resync_route_is_protected_from_guests_and_non_admins(): void
    {
        Queue::fake();

        $property = $this->propertyWithCoords();

        // Guest — bounced to login by the auth middleware.
        $this->post(route('admin.properties.resync-nearby-places', $property))
            ->assertRedirect(route('login'));

        // Authenticated but without the admin role — rejected by EnsureUserIsAdmin.
        $this->actingAs($this->user)
            ->post(route('admin.properties.resync-nearby-places', $property))
            ->assertForbidden();

        Queue::assertNotPushed(FetchNearbyPlacesJob::class);
    }

    public function test_admin_nearby_places_page_lists_synced_places(): void
    {
        $this->authenticate();

        $property = $this->propertyWithCoords();
        $this->seedPlace($property, ['name' => 'Admin Visible Cafe']);

        $response = $this->get(route('admin.properties.nearby-places', $property));

        $response->assertStatus(200);
        $response->assertSee('Admin Visible Cafe', false);
    }

    public function test_property_edit_page_renders_the_nearby_section(): void
    {
        $this->authenticate();

        $property = $this->propertyWithCoords();
        $this->seedPlace($property, ['name' => 'Edit Page Cafe']);

        $response = $this->get(route('admin.properties.edit', $property));

        $response->assertStatus(200);
        $response->assertSee('Edit Page Cafe', false);
        $response->assertSee(route('admin.properties.resync-nearby-places', $property), false);
    }

    public function test_property_edit_page_requires_saving_changed_map_coordinates_before_resync(): void
    {
        $this->authenticate();

        $property = $this->propertyWithCoords();

        $response = $this->get(route('admin.properties.edit', $property));

        $response->assertStatus(200);
        $response->assertSee('data-persisted-lat="', false);
        $response->assertSee('data-persisted-lng="', false);
        $response->assertSee(__('Save the property after changing the map pin, then resync POIs.'), false);
        $response->assertSee('coordinatesDiffer', false);
    }

    /* ===================================================================
     | Group 4 — Frontend rendering + no-live-API guarantee
     * =================================================================== */

    public function test_public_property_page_makes_zero_outbound_http_requests(): void
    {
        // CRITICAL: pins the "no external API call on a page render" rule.
        Http::preventStrayRequests();
        Http::fake();

        $property = $this->propertyWithCoords();
        $this->seedPlace($property, ['name' => 'Render Cafe']);

        $response = $this->get(route('properties.public.show', $property->slug));

        $response->assertStatus(200);
        Http::assertNothingSent();
    }

    public function test_public_property_page_shows_persistent_pois_with_distance(): void
    {
        Http::preventStrayRequests();
        Http::fake();

        $property = $this->propertyWithCoords();
        $this->seedPlace(
            $property,
            ['name' => 'Persistent Warung', 'category' => GeoapifyService::GROUP_HOSPITAL],
            ['distance_m' => 850]
        );

        $response = $this->get(route('properties.public.show', $property->slug));

        $response->assertStatus(200);
        $response->assertSee('Persistent Warung', false);
        $response->assertSee('850m', false);
    }

    public function test_public_property_page_prefers_the_walking_distance_when_measured(): void
    {
        Http::preventStrayRequests();
        Http::fake();

        $property = $this->propertyWithCoords();
        $this->seedPlace(
            $property,
            [
                'name' => 'Walked Hospital',
                'category' => GeoapifyService::GROUP_HOSPITAL,
                'address' => 'Jl. Detail No. 5, Jakarta',
                'website' => 'https://walked.example.test',
                'phone' => '+6221000111',
            ],
            ['distance_m' => 900, 'walking_distance_m' => 650, 'walking_duration_s' => 480]
        );

        $response = $this->get(route('properties.public.show', $property->slug));

        $response->assertStatus(200);
        $response->assertSee('650m', false);

        // The map payload carries the full place details for the marker popup.
        $payload = $this->extractMapData($response->getContent());
        $poi = collect($payload['markers'])->firstWhere('type', 'poi');

        $this->assertNotNull($poi, 'No POI marker in the map payload.');
        $this->assertSame('Walked Hospital', $poi['name']);
        $this->assertSame('650m', $poi['distance']);
        $this->assertSame('8 '.__('min walk'), $poi['walking']);
        $this->assertSame('Jl. Detail No. 5, Jakarta', $poi['address']);
        $this->assertSame('https://walked.example.test', $poi['website']);
        $this->assertSame('+6221000111', $poi['phone']);
    }

    public function test_map_payload_never_contains_the_geopify_api_key(): void
    {
        config()->set('services.geoapify.key', 'payload-secret-key');
        config()->set('services.geoapify.map_key', 'payload-map-key');

        Http::preventStrayRequests();
        Http::fake();

        $property = $this->propertyWithCoords();
        $this->seedPlace($property, ['name' => 'Payload Hospital']);

        $response = $this->get(route('properties.public.show', $property->slug));

        $response->assertStatus(200);

        // The payload may carry the browser map key (tiles need it) but never the
        // server-side Places/Route-Matrix key.
        $this->assertStringNotContainsString('payload-secret-key', $response->getContent());
        $this->assertStringContainsString('payload-map-key', $response->getContent());
    }

    public function test_public_property_page_falls_back_to_manual_nearby_places(): void
    {
        // Backward compatibility for the manual JSON column: a property with no
        // property_places rows must still render its manual entries.
        // (PropertyNearbyPlacesTest covers admin persistence of that column; this
        // only pins the public fallback branch.)
        Http::preventStrayRequests();
        Http::fake();

        $property = $this->propertyWithCoords([
            'nearby_places' => [
                ['name' => 'Manual AEON Mall', 'category' => 'Mall/Shopping', 'lat' => -6.302, 'lng' => 106.652],
            ],
        ]);

        $this->assertSame(0, PropertyPlace::where('property_id', $property->id)->count());

        $response = $this->get(route('properties.public.show', $property->slug));

        $response->assertStatus(200);
        $response->assertSee('Manual AEON Mall', false);
    }

    public function test_public_property_page_renders_map_container_and_payload(): void
    {
        Http::preventStrayRequests();
        Http::fake();

        $property = $this->propertyWithCoords();
        $this->seedPlace($property, [
            'name' => 'Mapped Cafe',
            'lat' => -6.205,
            'lng' => 106.805,
        ]);

        $response = $this->get(route('properties.public.show', $property->slug));

        $response->assertStatus(200);
        $response->assertSee('id="property-map"', false);
        $response->assertSee('id="map-data"', false);

        $payload = $this->extractMapData($response->getContent());

        $this->assertSame([-6.2, 106.8], $payload['center']);
        $this->assertSame(config('services.geoapify.map_key'), $payload['mapKey']);
        $this->assertSame('test-map-key', $payload['mapKey']);

        $types = array_column($payload['markers'], 'type');
        $this->assertContains('property', $types);
        $this->assertContains('poi', $types);
        $this->assertContains('Mapped Cafe', array_column($payload['markers'], 'name'));
    }

    public function test_public_property_page_without_coordinates_has_no_map_container(): void
    {
        Http::preventStrayRequests();
        Http::fake();

        // No property coordinates and no POI coordinates => nothing to plot.
        $property = Property::factory()->create([
            'status' => 'published',
            'latitude' => null,
            'longitude' => null,
            'nearby_places' => [
                ['name' => 'Coordless Mall', 'category' => 'Mall/Shopping', 'lat' => null, 'lng' => null],
            ],
        ]);

        $response = $this->get(route('properties.public.show', $property->slug));

        $response->assertStatus(200);
        // The "What's Around" list still renders...
        $response->assertSee('Coordless Mall', false);
        // ...but the Leaflet container and payload do not.
        $response->assertDontSee('id="property-map"', false);
        $response->assertDontSee('id="map-data"', false);
    }

    /* ===================================================================
     | Group 5 — Model behaviour
     * =================================================================== */

    public function test_distance_formatted_accessor_formats_metres_and_kilometres(): void
    {
        $property = $this->propertyWithCoords();

        $under = $this->seedPlace($property, ['name' => 'Close Place'], ['distance_m' => 850]);
        $over = $this->seedPlace($property, ['name' => 'Far Place'], ['distance_m' => 1200]);

        $this->assertSame('850m', $under->distance_formatted);
        $this->assertSame('1.2km', $over->distance_formatted);

        // A null distance yields null rather than a bare unit string.
        $unknown = $this->seedPlace($property, ['name' => 'Unknown Place'], ['distance_m' => null]);
        $this->assertNull($unknown->distance_formatted);
    }

    public function test_walking_accessors_render_time_and_distance(): void
    {
        $property = $this->propertyWithCoords();

        $pivot = $this->seedPlace(
            $property,
            ['name' => 'Walked Place'],
            ['distance_m' => 900, 'walking_distance_m' => 650, 'walking_duration_s' => 480]
        );

        // 480s => 8 minutes.
        $this->assertSame(8, $pivot->walking_minutes);
        $this->assertSame('8 '.__('min walk'), $pivot->walking_duration_formatted);
        $this->assertSame('650m', $pivot->walking_distance_formatted);
        // distance_formatted prefers the measured walking distance.
        $this->assertSame('650m', $pivot->distance_formatted);
    }

    public function test_deleting_a_property_cascades_to_its_property_places(): void
    {
        // config('database.connections.sqlite.foreign_key_constraints') defaults to
        // true, so the FK cascade is genuinely enforced here. Guard the assumption
        // explicitly so this test can never pass for the wrong reason.
        $this->assertSame(
            1,
            (int) DB::selectOne('PRAGMA foreign_keys')->foreign_keys,
            'SQLite foreign keys are disabled; the cascade assertion would be meaningless.'
        );

        $property = $this->propertyWithCoords();
        $pivot = $this->seedPlace($property, ['name' => 'Cascade Cafe']);

        // Property uses SoftDeletes, so delete() only sets deleted_at — the row
        // survives and the DB-level cascade does NOT fire. Pin that first so the
        // real cascade assertion below cannot pass for the wrong reason.
        $property->delete();
        $this->assertSoftDeleted('properties', ['id' => $property->id]);
        $this->assertDatabaseHas('property_places', ['id' => $pivot->id]);

        // A hard delete removes the properties row and the FK cascade fires.
        $property->forceDelete();

        $this->assertDatabaseMissing('properties', ['id' => $property->id]);
        $this->assertDatabaseMissing('property_places', ['id' => $pivot->id]);
        // The shared `places` row itself is intentionally NOT removed.
        $this->assertDatabaseHas('places', ['id' => $pivot->place_id]);
    }

    /* ===================================================================
     | Group 6 — Security hardening (SEC-001 / SEC-002 / SEC-003 / SEC-004 / SEC-007)
     * =================================================================== */

    /**
     * SEC-001 — the resync route is capped at 5 attempts per 10 minutes.
     *
     * The rate limiter uses the cache store, which is the `array` driver here and
     * is therefore rebuilt for every test — no cross-test bleed to clean up.
     */
    public function test_resync_route_is_throttled_after_five_attempts(): void
    {
        Queue::fake();
        $this->authenticate();

        $property = $this->propertyWithCoords();
        $url = route('admin.properties.resync-nearby-places', $property);

        for ($attempt = 1; $attempt <= 5; $attempt++) {
            $this->post($url)->assertStatus(302);
        }

        // The 6th attempt inside the window is rejected by the throttle middleware.
        $this->post($url)->assertStatus(429);

        // ...and it must not have enqueued another paid Geoapify fetch.
        Queue::assertPushed(FetchNearbyPlacesJob::class, 5);
    }

    /**
     * SEC-002 — a second concurrent sync for the same property early-returns while
     * the per-property lock is held, so at most one upstream call is made.
     */
    public function test_job_early_returns_while_the_property_sync_lock_is_held(): void
    {
        $property = $this->propertyWithCoords();

        $this->fakeGeoapify([
            $this->poiFeature(['place_id' => 'gp-locked', 'name' => 'Locked Out Hospital']),
        ]);

        // Simulate the in-flight sibling job by taking the lock first.
        $lock = Cache::lock("geoapify_sync_{$property->id}", 120);
        $this->assertTrue($lock->get(), 'The configured cache store must support atomic locks.');

        try {
            $result = (new FetchNearbyPlacesJob($property))->handle();

            $this->assertSame('busy', $result['reason']);

            Http::assertNothingSent();
            $this->assertDatabaseCount('places', 0);
            $this->assertDatabaseCount('property_places', 0);
            $this->assertNull(Cache::get("geoapify_places_{$property->id}"));
        } finally {
            $lock->release();
        }

        // Once the lock is free the same job runs normally — the lock is released,
        // not leaked, by the successful path.
        (new FetchNearbyPlacesJob($property))->handle();

        Http::assertSentCount(7);
        $this->assertDatabaseCount('places', 1);
        $this->assertTrue(Cache::lock("geoapify_sync_{$property->id}", 120)->get());
    }

    /**
     * SEC-004 — a connection failure becomes a generic RuntimeException whose
     * message cannot contain the request URL (and therefore the apiKey).
     */
    public function test_service_connection_failure_never_exposes_the_api_key(): void
    {
        Http::preventStrayRequests();
        Http::fake([
            // Guzzle's real message embeds the full URL including apiKey=...
            'api.geoapify.com/*' => fn () => throw new ConnectionException(
                'cURL error 6: Could not resolve host (see https://api.geoapify.com/v2/places?apiKey=test-key)'
            ),
        ]);

        try {
            (new GeoapifyService)->searchGroup(GeoapifyService::GROUP_HOSPITAL, -6.2, 106.8);
            $this->fail('Expected a RuntimeException for a connection failure.');
        } catch (\RuntimeException $e) {
            $this->assertSame('Geoapify API request failed: connection error', $e->getMessage());
            $this->assertStringNotContainsString('apiKey', $e->getMessage());
            $this->assertStringNotContainsString('test-key', $e->getMessage());
            // No $previous chaining — a debug trace cannot re-expose the URL.
            $this->assertNull($e->getPrevious());
        }
    }

    /**
     * SEC-004 + SEC-005 — the job swallows the connection failure, caches nothing,
     * and logs no credential material.
     */
    public function test_job_handles_a_connection_failure_without_throwing_or_logging_the_key(): void
    {
        $property = $this->propertyWithCoords();

        Http::preventStrayRequests();
        Http::fake([
            'api.geoapify.com/*' => fn () => throw new ConnectionException(
                'cURL error 6: Could not resolve host (see https://api.geoapify.com/v2/places?apiKey=test-key)'
            ),
        ]);

        $logged = [];

        Log::listen(function ($message) use (&$logged): void {
            $logged[] = $message->message;
        });

        // Must not throw — the job records the per-group failure and returns.
        $result = (new FetchNearbyPlacesJob($property))->handle();

        $this->assertFalse($result['success']);
        $this->assertDatabaseCount('places', 0);
        $this->assertDatabaseCount('property_places', 0);
        $this->assertNull(Cache::get("geoapify_places_{$property->id}"));

        $this->assertNotEmpty($logged, 'The failure should still be logged.');

        foreach ($logged as $line) {
            $this->assertStringNotContainsString('apiKey', $line);
            $this->assertStringNotContainsString('test-key', $line);
        }
    }

    /**
     * SEC-007 — markup in Geoapify strings is stripped before persistence, and the
     * public page renders nothing that looks like a tag.
     */
    public function test_geoapify_supplied_markup_is_sanitized_before_persistence(): void
    {
        $property = $this->propertyWithCoords();

        $this->fakeGeoapify([
            $this->poiFeature([
                'place_id' => 'gp-xss',
                'name' => '<script>alert(1)</script>',
                'formatted' => "Jl. <img src=x onerror=alert(1)>\n  Nakal",
            ]),
        ]);

        (new FetchNearbyPlacesJob($property))->handle();

        $place = Place::firstOrFail();

        $this->assertStringNotContainsString('<', $place->name);
        $this->assertStringNotContainsString('<', $place->address);
        $this->assertSame('alert(1)', $place->name);
        // Whitespace runs are collapsed too.
        $this->assertSame('Jl. Nakal', $place->address);

        $response = $this->get(route('properties.public.show', $property->slug));

        $response->assertStatus(200);
        $response->assertDontSee('<script>alert(1)</script>', false);
        $response->assertDontSee('<img src=x onerror=alert(1)>', false);
    }

    /**
     * SEC-007 — over-long Geoapify strings are clamped to the DB column widths.
     */
    public function test_geoapify_supplied_strings_are_clamped_to_column_lengths(): void
    {
        $property = $this->propertyWithCoords();

        $this->fakeGeoapify([
            $this->poiFeature([
                'place_id' => str_repeat('p', 200),
                'name' => str_repeat('n', 400),
                'formatted' => str_repeat('a', 700),
                'website' => str_repeat('w', 700),
                'contact' => ['phone' => str_repeat('9', 120)],
            ]),
        ]);

        (new FetchNearbyPlacesJob($property))->handle();

        $place = Place::firstOrFail();

        $this->assertSame(128, mb_strlen($place->geoapify_place_id));
        $this->assertSame(255, mb_strlen($place->name));
        $this->assertSame(500, mb_strlen($place->address));
        $this->assertSame(500, mb_strlen($place->website));
        $this->assertSame(50, mb_strlen($place->phone));
    }

    /**
     * SEC-003 — the admin property edit page warns when the browser map key is the
     * server Places key.
     */
    public function test_property_edit_page_warns_when_the_map_key_equals_the_places_key(): void
    {
        $this->authenticate();

        config()->set('services.geoapify.key', 'shared-key');
        config()->set('services.geoapify.map_key', 'shared-key');

        $property = $this->propertyWithCoords();

        $this->get(route('admin.properties.edit', $property))
            ->assertStatus(200)
            ->assertSee('GEOAPIFY_MAP_KEY belum diatur', false);
    }

    public function test_property_edit_page_has_no_shared_key_warning_when_keys_differ(): void
    {
        $this->authenticate();

        config()->set('services.geoapify.key', 'server-places-key');
        config()->set('services.geoapify.map_key', 'restricted-browser-key');

        $property = $this->propertyWithCoords();

        $this->get(route('admin.properties.edit', $property))
            ->assertStatus(200)
            ->assertDontSee('GEOAPIFY_MAP_KEY belum diatur', false);
    }
}
