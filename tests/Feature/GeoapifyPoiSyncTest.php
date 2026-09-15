<?php

namespace Tests\Feature;

use App\Models\Place;
use App\Models\Property;
use App\Models\PropertyPlace;
use App\Models\Role;
use App\Models\Setting;
use App\Models\User;
use App\Services\GeoapifyService;
use App\Services\SettingsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Geoapify POI integration — settings, walking-time filtering, partial failure
 * and the end-to-end Create/Edit property flows.
 *
 * The companion file GeoapifyNearbyPlacesTest covers the pipeline internals and
 * the security hardening (SEC-001..007); this file pins the operator-facing
 * requirements: a key configured from Settings, POIs synced from the property
 * screen, the 15-minute WALKING-TIME budget, and graceful degradation.
 *
 * Every test fakes both Geoapify endpoints (Places + Route Matrix). No test ever
 * performs a real network call or uses a real API key.
 */
class GeoapifyPoiSyncTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Dummy server-side key used throughout. Never a real credential.
     */
    private const TEST_KEY = 'unit-test-geoapify-key';

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();

        // SettingsService caches statically for the whole process — clear it so a
        // key written by another test can never leak in.
        SettingsService::clearCache();

        config()->set('services.geoapify.key', '');
        config()->set('services.geoapify.map_key', '');
        config()->set('services.geoapify.radius', 2000);
        config()->set('services.geoapify.max_results', 20);
    }

    /* ===================================================================
     | Helpers
     * =================================================================== */

    protected function authenticate(): void
    {
        $role = Role::updateOrCreate(['slug' => 'super-admin'], ['name' => 'Super Admin']);
        $this->user->roles()->syncWithoutDetaching([$role->id => ['model_type' => User::class]]);

        $this->actingAs($this->user);
    }

    /**
     * Store the Geoapify key the way the Settings screen does.
     */
    protected function configureApiKey(string $key = self::TEST_KEY): void
    {
        SettingsService::set('geoapify_api_key', $key, 'integrations');
    }

    /**
     * A published property with coordinates in Jakarta.
     *
     * @param  array<string, mixed>  $attributes
     */
    protected function propertyWithCoords(array $attributes = []): Property
    {
        return Property::factory()->create(array_merge([
            'status' => 'published',
            'latitude' => -6.2,
            'longitude' => 106.8,
        ], $attributes));
    }

    /**
     * One Geoapify Places feature.
     *
     * @param  array<string, mixed>  $properties
     * @param  array<int, float>|null  $coordinates  GeoJSON [lng, lat]
     * @return array<string, mixed>
     */
    protected function feature(array $properties = [], ?array $coordinates = null): array
    {
        return [
            'type' => 'Feature',
            'properties' => array_merge([
                'place_id' => 'gp-default',
                'name' => 'Default Place',
                'categories' => ['healthcare.hospital'],
                'formatted' => 'Jl. Contoh No. 1, Jakarta',
                'website' => null,
                'contact' => [],
            ], $properties),
            'geometry' => [
                'type' => 'Point',
                'coordinates' => $coordinates ?? [106.81, -6.21],
            ],
        ];
    }

    /**
     * Fake both Geoapify endpoints.
     *
     * @param  array<int, array<string, mixed>>  $features
     * @param  array<string, int|null>  $walkSeconds  place_id => routed walking seconds (default 300)
     * @param  callable|null  $placesResponder  Optional override for the Places endpoint
     */
    protected function fakeGeoapify(array $features, array $walkSeconds = [], ?callable $placesResponder = null): void
    {
        Http::preventStrayRequests();

        $coordinates = [];
        foreach ($features as $feature) {
            $coordinates[$feature['properties']['place_id']] = $feature['geometry']['coordinates'];
        }

        Http::fake([
            'api.geoapify.com/v2/places*' => $placesResponder
                ?: Http::response(['type' => 'FeatureCollection', 'features' => $features], 200),

            'api.geoapify.com/v1/routematrix*' => function ($request) use ($coordinates, $walkSeconds) {
                $row = [];

                foreach ($request->data()['targets'] ?? [] as $index => $target) {
                    [$lng, $lat] = $target['location'];
                    $placeId = null;

                    foreach ($coordinates as $id => [$poiLng, $poiLat]) {
                        if (abs($poiLng - $lng) < 1e-9 && abs($poiLat - $lat) < 1e-9) {
                            $placeId = $id;
                            break;
                        }
                    }

                    $seconds = $placeId === null ? null : ($walkSeconds[$placeId] ?? 300);

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

    /* ===================================================================
     | 1 — Geoapify API key setting
     * =================================================================== */

    public function test_geoapify_api_key_is_stored_in_settings_from_the_integrations_group(): void
    {
        $this->authenticate();

        $response = $this->post(route('admin.settings.update', ['group' => 'integrations']), [
            'notification_webhook' => '',
            'notification_webhook_secret' => '',
            'geoapify_api_key' => self::TEST_KEY,
            'geoapify_map_key' => 'referrer-restricted-map-key',
        ]);

        $response->assertRedirect(route('admin.settings.index', ['group' => 'integrations']));

        $this->assertDatabaseHas('settings', [
            'key' => 'geoapify_api_key',
            'group' => 'integrations',
        ]);

        $this->assertSame(self::TEST_KEY, SettingsService::get('geoapify_api_key'));
        $this->assertSame('referrer-restricted-map-key', SettingsService::get('geoapify_map_key'));

        // The stored key is the one the service actually uses.
        $this->assertSame(self::TEST_KEY, GeoapifyService::apiKey());
        $this->assertTrue(GeoapifyService::isConfigured());
    }

    public function test_settings_page_reports_the_key_as_configured_without_rendering_it(): void
    {
        $this->authenticate();
        $this->configureApiKey();

        $response = $this->get(route('admin.settings.index', ['group' => 'integrations']));

        $response->assertStatus(200);
        $response->assertSee('API key configured', false);

        // The secret itself must never reach the HTML, and the input is a password field.
        $response->assertDontSee(self::TEST_KEY, false);
        $response->assertSee('type="password"', false);
    }

    public function test_settings_page_reports_a_missing_key_as_not_configured(): void
    {
        $this->authenticate();

        $this->get(route('admin.settings.index', ['group' => 'integrations']))
            ->assertStatus(200)
            ->assertSee('Not configured', false);
    }

    public function test_blank_api_key_submission_keeps_the_stored_key(): void
    {
        $this->authenticate();
        $this->configureApiKey();

        $this->post(route('admin.settings.update', ['group' => 'integrations']), [
            'notification_webhook' => '',
            'notification_webhook_secret' => '',
            'geoapify_api_key' => '',
            'geoapify_map_key' => '',
        ])->assertRedirect();

        $this->assertSame(self::TEST_KEY, SettingsService::get('geoapify_api_key'));
        $this->assertSame(self::TEST_KEY, GeoapifyService::apiKey());
    }

    public function test_api_key_setting_overrides_the_env_fallback(): void
    {
        config()->set('services.geoapify.key', 'env-fallback-key');
        $this->configureApiKey('settings-key-wins');

        $this->assertSame('settings-key-wins', GeoapifyService::apiKey());
    }

    public function test_env_key_is_used_when_no_setting_is_stored(): void
    {
        config()->set('services.geoapify.key', 'env-fallback-key');

        $this->assertSame('env-fallback-key', GeoapifyService::apiKey());
    }

    public function test_api_key_never_reaches_the_property_edit_page_or_the_sync_response(): void
    {
        $this->authenticate();
        $this->configureApiKey('super-secret-geoapify-key');

        $property = $this->propertyWithCoords();

        $this->fakeGeoapify([
            $this->feature(['place_id' => 'gp-1', 'name' => 'RS Sehat']),
        ]);

        // The edit screen only reports the status, never the key.
        $this->get(route('admin.properties.edit', $property))
            ->assertStatus(200)
            ->assertSee(__('Geoapify API: configured'), false)
            ->assertDontSee('super-secret-geoapify-key', false);

        // The sync response (JSON, rendered in the browser) must not carry it either.
        $response = $this->postJson(route('admin.properties.resync-nearby-places', $property));

        $response->assertStatus(200);
        $this->assertStringNotContainsString('super-secret-geoapify-key', $response->getContent());
        $this->assertStringNotContainsString('apiKey', $response->getContent());
    }

    /* ===================================================================
     | 2/3 — Preconditions: coordinates and API key
     * =================================================================== */

    public function test_property_without_coordinates_cannot_synchronize(): void
    {
        $this->authenticate();
        $this->configureApiKey();

        $property = Property::factory()->create([
            'status' => 'published',
            'latitude' => null,
            'longitude' => null,
        ]);

        Http::preventStrayRequests();
        Http::fake();

        $response = $this->postJson(route('admin.properties.resync-nearby-places', $property));

        $response->assertStatus(422);
        $response->assertJson([
            'success' => false,
            'reason' => 'missing_coordinates',
        ]);

        Http::assertNothingSent();
        $this->assertDatabaseCount('places', 0);
    }

    public function test_property_edit_page_explains_that_coordinates_are_required(): void
    {
        $this->authenticate();
        $this->configureApiKey();

        $property = Property::factory()->create([
            'status' => 'published',
            'latitude' => null,
            'longitude' => null,
        ]);

        $this->get(route('admin.properties.edit', $property))
            ->assertStatus(200)
            ->assertSee(__('Coordinates are required to find nearby places. Set the property location on the map, save, then sync.'), false);
    }

    public function test_missing_api_key_is_reported_with_a_link_to_the_settings_group(): void
    {
        $this->authenticate();

        $property = $this->propertyWithCoords();

        Http::preventStrayRequests();
        Http::fake();

        $response = $this->postJson(route('admin.properties.resync-nearby-places', $property));

        $response->assertStatus(422);
        $response->assertJson([
            'success' => false,
            'reason' => 'missing_api_key',
        ]);

        Http::assertNothingSent();

        // The edit page points the operator at the exact settings group.
        $this->get(route('admin.properties.edit', $property))
            ->assertStatus(200)
            ->assertSee(__('Geoapify API: not configured'), false)
            ->assertSee(route('admin.settings.index', ['group' => 'integrations']), false);
    }

    /* ===================================================================
     | 4 — Authorization
     * =================================================================== */

    public function test_poi_synchronization_requires_an_authenticated_admin(): void
    {
        $this->configureApiKey();
        $property = $this->propertyWithCoords();

        Http::preventStrayRequests();
        Http::fake();

        // Guest: redirected to login, nothing fetched.
        $this->post(route('admin.properties.resync-nearby-places', $property))
            ->assertRedirect(route('login'));

        // Authenticated non-admin: forbidden.
        $this->actingAs($this->user)
            ->postJson(route('admin.properties.resync-nearby-places', $property))
            ->assertForbidden();

        Http::assertNothingSent();
        $this->assertDatabaseCount('places', 0);
    }

    /* ===================================================================
     | 5/6/9 — Persistence, idempotency, walking metrics
     * =================================================================== */

    public function test_geapify_results_are_persisted_with_walking_metrics(): void
    {
        $this->authenticate();
        $this->configureApiKey();

        $property = $this->propertyWithCoords();

        $this->fakeGeoapify(
            [$this->feature(['place_id' => 'gp-1', 'name' => 'RS Sehat'])],
            ['gp-1' => 480]
        );

        $response = $this->postJson(route('admin.properties.resync-nearby-places', $property));

        $response->assertStatus(200);
        $response->assertJson(['success' => true, 'count' => 1, 'partial' => false]);

        $place = Place::firstOrFail();
        $this->assertSame('RS Sehat', $place->name);
        $this->assertSame('healthcare.hospital', $place->category);
        $this->assertNotNull($place->fetched_at);

        $pivot = PropertyPlace::firstOrFail();
        $this->assertSame('geoapify', $pivot->source);
        $this->assertSame(480, $pivot->walking_duration_s);
        $this->assertSame(624, $pivot->walking_distance_m); // 480s * 1.3 in the fake
        $this->assertGreaterThan(0, $pivot->distance_m);
        $this->assertSame(8, $pivot->walking_minutes);

        // The admin table is re-rendered with the persisted rows.
        $response->assertJsonPath('html', fn ($html) => str_contains($html, 'RS Sehat'));
    }

    public function test_repeated_sync_does_not_create_duplicate_pois(): void
    {
        $this->authenticate();
        $this->configureApiKey();

        $property = $this->propertyWithCoords();

        $this->fakeGeoapify([$this->feature(['place_id' => 'gp-dup', 'name' => 'RS Duplikat'])]);

        $this->postJson(route('admin.properties.resync-nearby-places', $property))->assertStatus(200);

        // A resync clears the 24h cache, so the second call really re-fetches.
        $this->postJson(route('admin.properties.resync-nearby-places', $property))->assertStatus(200);

        $this->assertDatabaseCount('places', 1);
        $this->assertDatabaseCount('property_places', 1);
        $this->assertSame(1, PropertyPlace::where('property_id', $property->id)->count());
    }

    /* ===================================================================
     | 7/8 — The 15-minute WALKING-TIME budget
     * =================================================================== */

    public function test_pois_over_the_walking_budget_are_excluded(): void
    {
        $this->authenticate();
        $this->configureApiKey();

        $property = $this->propertyWithCoords();

        // 901s is one second past the 15-minute walking budget. Both POIs sit at
        // effectively the same spot (a few centimetres apart, so the fake can
        // still tell the two matrix targets apart), which means only a real
        // travel-time rule — not a distance rule — can separate them.
        $this->fakeGeoapify([
            $this->feature(['place_id' => 'gp-far', 'name' => 'RS Terlalu Jauh'], [106.81, -6.21]),
            $this->feature(['place_id' => 'gp-near', 'name' => 'RS Dekat'], [106.8100002, -6.2100002]),
        ], [
            'gp-far' => 901,
            'gp-near' => 300,
        ]);

        $response = $this->postJson(route('admin.properties.resync-nearby-places', $property));

        $response->assertStatus(200);
        $response->assertJson(['count' => 1]);

        $this->assertDatabaseHas('places', ['geoapify_place_id' => 'gp-near']);
        $this->assertDatabaseMissing('places', ['geoapify_place_id' => 'gp-far']);
        $this->assertSame(1, PropertyPlace::where('property_id', $property->id)->count());
    }

    public function test_pois_at_exactly_ten_minutes_walking_are_kept(): void
    {
        $this->authenticate();
        $this->configureApiKey();

        $property = $this->propertyWithCoords();

        $this->fakeGeoapify(
            [$this->feature(['place_id' => 'gp-edge', 'name' => 'Stasiun Tepat 10 Menit'])],
            ['gp-edge' => GeoapifyService::WALK_MAX_SECONDS]
        );

        $response = $this->postJson(route('admin.properties.resync-nearby-places', $property));

        $response->assertStatus(200);
        $response->assertJson(['count' => 1]);

        $this->assertSame(600, PropertyPlace::firstOrFail()->walking_duration_s);
    }

    public function test_route_matrix_is_called_with_walking_mode(): void
    {
        $this->authenticate();
        $this->configureApiKey();

        $property = $this->propertyWithCoords();

        $this->fakeGeoapify([$this->feature(['place_id' => 'gp-walk', 'name' => 'RS Jalan Kaki'])]);

        $this->postJson(route('admin.properties.resync-nearby-places', $property))->assertStatus(200);

        Http::assertSent(function ($request) {
            if (! str_contains($request->url(), '/v1/routematrix')) {
                return false;
            }

            $body = $request->data();

            return ($body['mode'] ?? null) === 'walk'
                && count($body['sources'] ?? []) === 1
                && count($body['targets'] ?? []) === 1;
        });
    }

    public function test_route_calculation_failure_persists_nothing_and_keeps_existing_rows(): void
    {
        $this->authenticate();
        $this->configureApiKey();

        $property = $this->propertyWithCoords();

        // An existing synced row must survive a failed route calculation.
        $existing = PropertyPlace::create([
            'property_id' => $property->id,
            'place_id' => Place::create([
                'geoapify_place_id' => 'gp-existing',
                'name' => 'RS Lama',
                'category' => GeoapifyService::GROUP_HOSPITAL,
                'lat' => -6.205,
                'lng' => 106.805,
                'fetched_at' => now(),
            ])->id,
            'source' => 'geoapify',
            'distance_m' => 700,
        ]);

        Http::preventStrayRequests();
        Http::fake([
            'api.geoapify.com/v2/places*' => Http::response([
                'type' => 'FeatureCollection',
                'features' => [$this->feature(['place_id' => 'gp-new', 'name' => 'RS Baru'])],
            ], 200),
            'api.geoapify.com/v1/routematrix*' => Http::response('Routing unavailable', 500),
        ]);

        $response = $this->postJson(route('admin.properties.resync-nearby-places', $property));

        $response->assertStatus(422);
        $response->assertJson(['success' => false, 'reason' => 'route_failed']);

        // Nothing new persisted, nothing deleted.
        $this->assertDatabaseMissing('places', ['geoapify_place_id' => 'gp-new']);
        $this->assertDatabaseHas('property_places', ['id' => $existing->id]);
        $this->assertNull(Cache::get("geoapify_places_{$property->id}"));
    }

    /* ===================================================================
     | 10 — Partial category failure
     * =================================================================== */

    public function test_partial_category_failure_keeps_successful_groups_and_reports_the_failure(): void
    {
        $this->authenticate();
        $this->configureApiKey();

        $property = $this->propertyWithCoords();

        // The Places endpoint succeeds for cafe + healthcare and fails for
        // every public_transport category.
        $this->fakeGeoapify(
            [
                $this->feature([
                    'place_id' => 'gp-cafe',
                    'name' => 'Kopi Kiosk',
                    'categories' => ['catering.cafe'],
                ]),
                $this->feature(['place_id' => 'gp-hospital', 'name' => 'RS Sehat']),
            ],
            [],
            function ($request) {
                $categories = $request->data()['categories'] ?? '';

                if (str_contains($categories, 'public_transport')) {
                    return Http::response('Upstream failure', 500);
                }

                return Http::response([
                    'type' => 'FeatureCollection',
                    'features' => [
                        $this->feature([
                            'place_id' => 'gp-cafe',
                            'name' => 'Kopi Kiosk',
                            'categories' => ['catering.cafe'],
                        ]),
                        $this->feature(['place_id' => 'gp-hospital', 'name' => 'RS Sehat']),
                    ],
                ], 200);
            }
        );

        $response = $this->postJson(route('admin.properties.resync-nearby-places', $property));

        // The action itself succeeded — but it must not claim total success.
        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'partial' => true,
            'count' => 2,
        ]);

        // The message names the failed category (locale-independent assertions;
        // no place_categories rows are seeded here, so the raw slug is shown).
        $this->assertStringContainsString('public_transport', $response->json('message'));
        $this->assertStringContainsString('2', $response->json('message'));
        $this->assertSame('failed', $response->json('categories.public_transport.status'));

        // The two successful categories are persisted.
        $this->assertDatabaseHas('places', ['geoapify_place_id' => 'gp-cafe']);
        $this->assertDatabaseHas('places', ['geoapify_place_id' => 'gp-hospital']);
        $this->assertSame(2, PropertyPlace::where('property_id', $property->id)->count());
    }

    public function test_failed_group_keeps_its_previously_synced_rows(): void
    {
        $this->authenticate();
        $this->configureApiKey();

        $property = $this->propertyWithCoords();

        // A transport POI synced earlier must survive a sync in which the
        // transportation search fails.
        $transportPlace = Place::create([
            'geoapify_place_id' => 'gp-old-station',
            'name' => 'Stasiun Lama',
            'category' => 'public_transport',
            'lat' => -6.205,
            'lng' => 106.805,
            'fetched_at' => now(),
        ]);

        $pivot = PropertyPlace::create([
            'property_id' => $property->id,
            'place_id' => $transportPlace->id,
            'source' => 'geoapify',
            'distance_m' => 600,
        ]);

        // Only the tourism category succeeds; healthcare and transportation fail.
        Http::preventStrayRequests();
        Http::fake([
            'api.geoapify.com/v2/places*' => function ($request) {
                $categories = $request->data()['categories'] ?? '';

                if (str_contains($categories, 'tourism')) {
                    return Http::response([
                        'type' => 'FeatureCollection',
                        'features' => [$this->feature([
                            'place_id' => 'gp-mall-2',
                            'name' => 'Wisata Baru',
                            'categories' => ['tourism'],
                        ])],
                    ], 200);
                }

                return Http::response('Upstream failure', 500);
            },
            'api.geoapify.com/v1/routematrix*' => Http::response([
                'sources_to_targets' => [[['distance' => 400, 'time' => 300, 'source_index' => 0, 'target_index' => 0]]],
            ], 200),
        ]);

        $response = $this->postJson(route('admin.properties.resync-nearby-places', $property));

        $response->assertStatus(200);
        $response->assertJson(['partial' => true]);

        // The transport row from the failed group is untouched...
        $this->assertDatabaseHas('property_places', ['id' => $pivot->id]);
        // ...and the successful group's row was added.
        $this->assertDatabaseHas('places', ['geoapify_place_id' => 'gp-mall-2']);
    }

    /* ===================================================================
     | 11/12 — Create and Edit property flows
     * =================================================================== */

    public function test_create_property_flow_then_sync_persists_pois(): void
    {
        $this->authenticate();
        $this->configureApiKey();

        $this->fakeGeoapify([
            $this->feature(['place_id' => 'gp-created', 'name' => 'RS Setelah Create']),
        ]);

        // 1. Create the property (the create screen cannot sync: there is no id yet).
        $this->post(route('admin.properties.store'), [
            'name' => 'Apartemen Baru',
            'slug' => 'apartemen-baru',
            'status' => 'published',
            'latitude' => -6.2,
            'longitude' => 106.8,
        ])->assertRedirect(route('admin.properties.index'));

        $property = Property::where('slug', 'apartemen-baru')->firstOrFail();

        // 2. The create screen told the operator to save first — the saved property
        //    is now syncable from its edit screen.
        $this->get(route('admin.properties.edit', $property))
            ->assertStatus(200)
            ->assertSee('id="poi-resync-btn"', false)
            ->assertSee(__('Sync Nearby POI'), false);

        // 3. Sync against the freshly created property id.
        $response = $this->postJson(route('admin.properties.resync-nearby-places', $property));

        $response->assertStatus(200);
        $response->assertJson(['success' => true, 'count' => 1]);

        $this->assertDatabaseHas('places', ['geoapify_place_id' => 'gp-created']);
        $this->assertDatabaseHas('property_places', ['property_id' => $property->id, 'source' => 'geoapify']);
    }

    public function test_create_screen_explains_that_the_property_must_be_saved_first(): void
    {
        $this->authenticate();
        $this->configureApiKey();

        $this->get(route('admin.properties.create'))
            ->assertStatus(200)
            ->assertSee(__('Save the property first (with the map location), then open it again to sync POIs from Geoapify.'), false)
            ->assertSee(__('Sync Nearby POI'), false);
    }

    public function test_edit_property_flow_syncs_in_place_and_updates_existing_rows(): void
    {
        $this->authenticate();
        $this->configureApiKey();

        $property = $this->propertyWithCoords();

        // A previously synced POI that is now farther than 15 minutes on foot.
        $stalePlace = Place::create([
            'geoapify_place_id' => 'gp-stale',
            'name' => 'RS Lama',
            'category' => GeoapifyService::GROUP_HOSPITAL,
            'lat' => -6.21,
            'lng' => 106.81,
            'fetched_at' => now()->subDay(),
        ]);

        $stalePivot = PropertyPlace::create([
            'property_id' => $property->id,
            'place_id' => $stalePlace->id,
            'source' => 'geoapify',
            'distance_m' => 1500,
        ]);

        $this->fakeGeoapify([
            $this->feature(['place_id' => 'gp-updated', 'name' => 'RS Baru Dekat']),
        ], ['gp-updated' => 420]);

        $response = $this->postJson(route('admin.properties.resync-nearby-places', $property));

        $response->assertStatus(200);
        $response->assertJson(['success' => true, 'partial' => false, 'count' => 1]);

        // The replacement row is persisted with its walking metrics...
        $pivot = PropertyPlace::firstOrFail();
        $this->assertSame(420, $pivot->walking_duration_s);
        $this->assertSame(7, $pivot->walking_minutes);

        // ...and the stale row is gone.
        $this->assertDatabaseMissing('property_places', ['id' => $stalePivot->id]);

        // The table swap payload reflects the persisted state.
        $this->assertStringContainsString('RS Baru Dekat', $response->json('html'));
        $this->assertStringContainsString('7 '.__('min walk'), $response->json('html'));

        // The edit page renders the grouped, persisted results.
        $this->get(route('admin.properties.edit', $property))
            ->assertStatus(200)
            ->assertSee('RS Baru Dekat', false)
            ->assertSee('Hospital/Health', false);
    }

    public function test_sync_with_no_results_within_walking_range_reports_an_empty_result(): void
    {
        $this->authenticate();
        $this->configureApiKey();

        $property = $this->propertyWithCoords();

        // Every candidate is 30 minutes away on foot.
        $this->fakeGeoapify(
            [$this->feature(['place_id' => 'gp-far-away', 'name' => 'RS Sangat Jauh'])],
            ['gp-far-away' => 1800]
        );

        $response = $this->postJson(route('admin.properties.resync-nearby-places', $property));

        $response->assertStatus(200);
        $response->assertJson(['success' => true, 'count' => 0]);
        $this->assertSame(
            __('No nearby places found within a 15-minute walk of this property.'),
            $response->json('message')
        );

        $this->assertDatabaseCount('property_places', 0);
        $this->assertDatabaseCount('places', 0);
    }

    public function test_sync_does_not_delete_manual_nearby_places_json(): void
    {
        $this->authenticate();
        $this->configureApiKey();

        $property = $this->propertyWithCoords([
            'nearby_places' => [
                ['name' => 'Manual Mall', 'category' => 'Mall/Shopping', 'lat' => -6.302, 'lng' => 106.652],
            ],
        ]);

        $this->fakeGeoapify([$this->feature(['place_id' => 'gp-1', 'name' => 'RS Sehat'])]);

        $this->postJson(route('admin.properties.resync-nearby-places', $property))->assertStatus(200);

        $this->assertSame(
            'Manual Mall',
            $property->fresh()->nearby_places[0]['name']
        );
    }

    public function test_unexpected_failure_is_reported_as_a_generic_error_not_a_server_error(): void
    {
        $this->authenticate();
        $this->configureApiKey();

        $property = $this->propertyWithCoords();

        // A non-RuntimeException escapes the service's per-group handling, so this
        // exercises the controller's own catch (database/unexpected failures).
        Http::preventStrayRequests();
        Http::fake([
            'api.geoapify.com/*' => fn () => throw new \LogicException('internal boom'),
        ]);

        $response = $this->postJson(route('admin.properties.resync-nearby-places', $property));

        $response->assertStatus(422);
        $response->assertJson(['success' => false]);
        // The operator gets a short, actionable message — never the raw error.
        $this->assertStringNotContainsString('internal boom', $response->getContent());
        $this->assertStringNotContainsString(self::TEST_KEY, $response->getContent());
    }

    public function test_settings_model_keeps_the_key_out_of_the_settings_index_dump(): void
    {
        // The settings page is a normal Blade render, so the strongest guarantee
        // available is that the raw value is never assigned to the view data: only
        // booleans are. Pin that contract directly.
        $this->authenticate();
        $this->configureApiKey();

        $response = $this->get(route('admin.settings.index', ['group' => 'integrations']));

        $response->assertStatus(200);
        $response->assertViewHas('settings', function (array $settings): bool {
            return array_key_exists('geoapify_api_key_configured', $settings)
                && $settings['geoapify_api_key_configured'] === true
                && ! array_key_exists('geoapify_api_key', $settings);
        });

        $this->assertDatabaseHas('settings', ['key' => 'geoapify_api_key']);
        $this->assertSame(self::TEST_KEY, Setting::where('key', 'geoapify_api_key')->value('value'));
    }
}
