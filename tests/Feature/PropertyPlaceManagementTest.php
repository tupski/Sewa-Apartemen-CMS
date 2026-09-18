<?php

namespace Tests\Feature;

use App\Jobs\FetchNearbyPlacesJob;
use App\Models\Place;
use App\Models\Property;
use App\Models\PropertyPlace;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Phase 3 completion — inline POI row management (visibility + custom name)
 * on the property Create/Edit screen.
 *
 * Pins: IDOR protection (a pivot row from another property 404s), validation,
 * custom-name fallback semantics, XSS-safe output, and show_on_frontend
 * actually hiding the row from the public property page.
 */
class PropertyPlaceManagementTest extends TestCase
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

    protected function propertyWithPoi(array $pivotAttributes = []): array
    {
        $property = Property::factory()->create([
            'status' => 'published',
            'latitude' => -6.2,
            'longitude' => 106.8,
        ]);

        $place = Place::create([
            'geoapify_place_id' => 'gp-'.uniqid(),
            'name' => 'RS Sehat',
            'category' => 'healthcare.hospital',
            'lat' => -6.205,
            'lng' => 106.805,
            'address' => 'Jl. Contoh No. 1',
            'fetched_at' => now(),
        ]);

        $pivot = PropertyPlace::create(array_merge([
            'property_id' => $property->id,
            'place_id' => $place->id,
            'source' => 'geoapify',
            'distance_m' => 700,
            'show_on_frontend' => true,
        ], $pivotAttributes));

        return [$property, $place, $pivot];
    }

    protected function patchUrl(Property $property, PropertyPlace $pivot): string
    {
        return route('admin.properties.places.update', ['property' => $property, 'place' => $pivot]);
    }

    /* ===================================================================
     | Visibility
     * =================================================================== */

    public function test_admin_can_toggle_visibility(): void
    {
        $this->authenticate();
        [$property, , $pivot] = $this->propertyWithPoi();

        $response = $this->patchJson($this->patchUrl($property, $pivot), [
            'show_on_frontend' => false,
            'custom_name' => '',
        ]);

        $response->assertStatus(200);
        $response->assertJson(['success' => true, 'place' => ['show_on_frontend' => false]]);

        $this->assertFalse($pivot->refresh()->show_on_frontend);
    }

    public function test_hidden_pois_disappear_from_the_public_property_page(): void
    {
        $this->authenticate();
        [$property, , $pivot] = $this->propertyWithPoi();

        // Visible by default: rendered.
        $this->get(route('properties.public.show', $property))
            ->assertOk()
            ->assertSee('RS Sehat', false);

        $this->patchJson($this->patchUrl($property, $pivot), [
            'show_on_frontend' => false,
            'custom_name' => '',
        ])->assertStatus(200);

        $this->get(route('properties.public.show', $property))
            ->assertOk()
            ->assertDontSee('RS Sehat', false);
    }

    public function test_a_pivot_row_from_another_property_is_rejected(): void
    {
        $this->authenticate();
        [$propertyA, , $pivotA] = $this->propertyWithPoi();
        [$propertyB] = $this->propertyWithPoi();

        // Try to update property B's row through property A's URL — IDOR.
        $this->patchJson($this->patchUrl($propertyB, $pivotA), [
            'show_on_frontend' => false,
            'custom_name' => '',
        ])->assertNotFound();

        // ...and through a non-existent property id.
        $this->patchJson(route('admin.properties.places.update', ['property' => 999999, 'place' => $pivotA]), [
            'show_on_frontend' => false,
        ])->assertNotFound();

        $this->assertTrue($pivotA->refresh()->show_on_frontend);
    }

    public function test_poi_editing_requires_an_authenticated_admin(): void
    {
        [$property, , $pivot] = $this->propertyWithPoi();
        $url = $this->patchUrl($property, $pivot);

        // Guest → redirect to login.
        $this->patch($url, ['show_on_frontend' => false])
            ->assertRedirect(route('login'));

        // Authenticated non-admin → forbidden.
        $this->actingAs($this->user)
            ->patchJson($url, ['show_on_frontend' => false])
            ->assertForbidden();

        $this->assertTrue($pivot->refresh()->show_on_frontend);
    }

    /* ===================================================================
     | Custom name
     * =================================================================== */

    public function test_custom_name_is_saved_and_used_for_display(): void
    {
        $this->authenticate();
        [$property, , $pivot] = $this->propertyWithPoi();

        $response = $this->patchJson($this->patchUrl($property, $pivot), [
            'show_on_frontend' => true,
            'custom_name' => 'RS Dekat Apartemen',
        ]);

        $response->assertStatus(200);
        $response->assertJson(['success' => true, 'place' => ['display_name' => 'RS Dekat Apartemen']]);

        // The provider name on the place row is untouched.
        $this->assertSame('RS Sehat', $pivot->refresh()->place->name);
        $this->assertSame('RS Dekat Apartemen', $pivot->custom_name);
        $this->assertSame('RS Dekat Apartemen', $pivot->display_name);
    }

    public function test_empty_custom_name_falls_back_to_the_provider_name(): void
    {
        $this->authenticate();
        [$property, , $pivot] = $this->propertyWithPoi(['custom_name' => 'Nama Lama']);

        $this->patchJson($this->patchUrl($property, $pivot), [
            'show_on_frontend' => true,
            'custom_name' => '   ',
        ])->assertStatus(200);

        $pivot->refresh();
        $this->assertNull($pivot->custom_name);
        $this->assertSame('RS Sehat', $pivot->display_name);
    }

    public function test_custom_name_survives_a_resync(): void
    {
        $this->authenticate();
        [$property, $place, $pivot] = $this->propertyWithPoi(['custom_name' => 'RS Dekat Apartemen']);

        config()->set('services.geoapify.key', 'test-key');
        config()->set('services.geoapify.map_key', 'test-map-key');

        Http::preventStrayRequests();
        Http::fake([
            'api.geoapify.com/v2/places*' => Http::response([
                'type' => 'FeatureCollection',
                'features' => [[
                    'type' => 'Feature',
                    'properties' => [
                        'place_id' => $place->geoapify_place_id,
                        'name' => 'RS Sehat (Nama Baru Dari Provider)',
                        'categories' => ['healthcare.hospital'],
                        'formatted' => 'Jl. Contoh No. 1, Jakarta',
                        'website' => null,
                        'contact' => [],
                    ],
                    'geometry' => ['type' => 'Point', 'coordinates' => [106.805, -6.205]],
                ]],
            ], 200),
            'api.geoapify.com/v1/routematrix*' => Http::response([
                'sources_to_targets' => [[['distance' => 700, 'time' => 300, 'source_index' => 0, 'target_index' => 0]]],
            ], 200),
        ]);

        FetchNearbyPlacesJob::dispatchSync($property);

        // The provider's new name updated the place row, but the custom
        // presentation name and the visibility choice survived the sync.
        $pivot->refresh();
        $this->assertSame('RS Dekat Apartemen', $pivot->custom_name);
        $this->assertSame('RS Sehat (Nama Baru Dari Provider)', $pivot->place->name);
        $this->assertSame('RS Dekat Apartemen', $pivot->display_name);
    }

    public function test_malicious_custom_name_is_escaped_on_output(): void
    {
        $this->authenticate();
        [$property, , $pivot] = $this->propertyWithPoi();

        $this->patchJson($this->patchUrl($property, $pivot), [
            'show_on_frontend' => true,
            'custom_name' => '<script>alert("xss")</script> & <b>bold</b>',
        ])->assertStatus(200);

        // Blade {{ }} escapes: the raw script tag must never reach the HTML.
        $this->get(route('admin.properties.edit', $property))
            ->assertOk()
            ->assertDontSee('<script>alert("xss")</script>', false);
    }

    public function test_custom_name_validation_rejects_overlong_values(): void
    {
        $this->authenticate();
        [$property, , $pivot] = $this->propertyWithPoi();

        $this->patchJson($this->patchUrl($property, $pivot), [
            'show_on_frontend' => true,
            'custom_name' => str_repeat('a', 256),
        ])->assertStatus(422);
    }

    /* ===================================================================
     | Edit screen renders the management table
     * =================================================================== */

    public function test_edit_screen_shows_visibility_state_and_custom_name(): void
    {
        $this->authenticate();
        [$property, , $pivot] = $this->propertyWithPoi(['custom_name' => 'RS Dekat Apartemen', 'show_on_frontend' => false]);

        $this->get(route('admin.properties.edit', $property))
            ->assertOk()
            ->assertSee('RS Dekat Apartemen', false)
            ->assertSee('RS Sehat', false)
            // The rows ride inside a double-quoted x-data attribute via @js(),
            // so " is escaped as \u0022 — a raw " there would truncate the
            // attribute and break Alpine (see AlpineXDataJsonSafetyTest).
            ->assertSee('\u0022show_on_frontend\u0022:false', false);
    }
}
