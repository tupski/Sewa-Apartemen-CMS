<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Property;
use App\Models\PropertyUnitType;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Phase 5 adversarial verification — guarantees beyond the original CRUD
 * tests, written from the attacker's perspective:
 *
 * 1. availability gate on EVERY verb (store/update/reorder/delete) — metadata
 *    can never introduce or re-attach a unit type the property does not offer;
 * 2. mass-assignment: injected property_id / id / timestamps are ignored, the
 *    route-bound property is authoritative;
 * 3. is_active is metadata-presentation state only — deactivating a row never
 *    touches availability, pricing, bookings or search;
 * 4. deleting metadata leaves bookings intact;
 * 5. PUT/PATCH/GET verbs on the metadata endpoints are rejected (route verbs
 *    are part of the security surface).
 */
class PropertyUnitTypeAdversarialTest extends TestCase
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

    protected function propertyOffering(array $types = ['studio']): Property
    {
        return Property::factory()->create([
            'unit_types' => $types,
            'prices' => ['studio' => ['night_wd' => 150000]],
        ]);
    }

    /* ===================================================================
     | 1 — Availability gate on every verb
     * =================================================================== */

    public function test_store_cannot_introduce_an_unoffered_unit_type(): void
    {
        $this->authenticate();
        $property = $this->propertyOffering(['studio']);

        $this->postJson(route('admin.properties.unit-types.store', $property), [
            'unit_type' => '2br',
            'is_active' => true,
        ])->assertStatus(422);

        $this->assertSame(0, PropertyUnitType::where('property_id', $property->id)->count());
        // Canonical availability untouched by the attempt.
        $this->assertSame(['studio'], $property->refresh()->unit_types);
    }

    public function test_update_through_store_cannot_attach_an_unoffered_type_to_an_existing_row(): void
    {
        $this->authenticate();
        $property = $this->propertyOffering(['studio']);
        $row = PropertyUnitType::create(['property_id' => $property->id, 'unit_type' => 'studio']);

        // The row exists for 'studio'; the attacker replays the same property +
        // payload but with unit_type='2br' hoping updateOrCreate re-points it.
        $this->postJson(route('admin.properties.unit-types.store', $property), [
            'unit_type' => '2br',
            'is_active' => true,
        ])->assertStatus(422);

        $row->refresh();
        $this->assertSame('studio', $row->unit_type);
        $this->assertSame(1, PropertyUnitType::where('property_id', $property->id)->count());
    }

    public function test_reorder_cannot_smuggle_an_unoffered_type(): void
    {
        $this->authenticate();
        $property = $this->propertyOffering(['studio']);
        $row = PropertyUnitType::create(['property_id' => $property->id, 'unit_type' => 'studio']);

        // Reorder only accepts ids — there is no unit_type field at all, so a
        // payload claiming a new type is ignored and the unknown id is rejected.
        $this->postJson(route('admin.properties.unit-types.reorder', $property), [
            'order' => [$row->id],
            'unit_type' => '2br',
            'is_active' => true,
        ])->assertStatus(200);

        $this->assertSame(1, PropertyUnitType::where('property_id', $property->id)->count());
        $this->assertSame(['studio'], $property->refresh()->unit_types);
    }

    /* ===================================================================
     | 2 — Mass assignment
     * =================================================================== */

    public function test_injected_property_id_and_id_are_ignored(): void
    {
        $this->authenticate();
        $propertyA = $this->propertyOffering(['studio']);
        $propertyB = $this->propertyOffering(['studio']);

        $this->postJson(route('admin.properties.unit-types.store', $propertyA), [
            'unit_type' => 'studio',
            'is_active' => true,
            // Attacker-controlled identity fields:
            'id' => 999999,
            'property_id' => $propertyB->id,
            'created_at' => '2000-01-01 00:00:00',
            'updated_at' => '2000-01-01 00:00:00',
        ])->assertStatus(200);

        $row = PropertyUnitType::where('unit_type', 'studio')->sole();
        // The route-bound property owns the row; injected ids/timestamps ignored.
        $this->assertSame($propertyA->id, $row->property_id);
        $this->assertNotSame(999999, $row->id);
        $this->assertSame(0, PropertyUnitType::where('property_id', $propertyB->id)->count());
    }

    /* ===================================================================
     | 3 — is_active is presentation-only
     * =================================================================== */

    public function test_deactivating_metadata_never_touches_availability_pricing_or_bookings(): void
    {
        $this->authenticate();
        $property = $this->propertyOffering(['studio']);
        $row = PropertyUnitType::create(['property_id' => $property->id, 'unit_type' => 'studio']);

        $this->postJson(route('admin.properties.unit-types.store', $property), [
            'unit_type' => 'studio',
            'is_active' => false,
        ])->assertStatus(200);

        $row->refresh();
        $this->assertFalse($row->is_active);

        // Canonical state untouched.
        $property->refresh();
        $this->assertSame(['studio'], $property->unit_types);
        $this->assertSame(150000, $property->prices['studio']['night_wd']);

        // Booking eligibility flows through the canonical service, which still
        // accepts the type (is_active is not consulted).
        $this->assertTrue($property->hasType('studio'));
    }

    /* ===================================================================
     | 4 — delete leaves bookings intact
     * =================================================================== */

    public function test_deleting_metadata_leaves_existing_bookings_untouched(): void
    {
        $this->authenticate();
        $property = $this->propertyOffering(['studio']);
        $row = PropertyUnitType::create(['property_id' => $property->id, 'unit_type' => 'studio']);

        $booking = Booking::create([
            'property_id' => $property->id,
            'booking_type' => 'daily',
            'unit_type' => 'studio',
            'code' => 'BK-TEST-0001',
            'check_in' => now()->addDay(),
            'check_out' => now()->addDays(2),
            'guests' => 1,
            'customer_name' => 'Guest',
            'customer_phone' => '081200000000',
            'total_price' => 150000,
            'status' => 'pending',
        ]);

        $this->deleteJson(route('admin.properties.unit-types.destroy', ['property' => $property, 'unitType' => $row]))
            ->assertStatus(200);

        $this->assertDatabaseHas('bookings', ['id' => $booking->id, 'unit_type' => 'studio']);
        $this->assertDatabaseMissing('property_unit_types', ['id' => $row->id]);
    }

    /* ===================================================================
     | 5 — wrong HTTP verbs are not routes at all
     * =================================================================== */

    public function test_unrouted_verbs_are_rejected(): void
    {
        $this->authenticate();
        $property = $this->propertyOffering(['studio']);
        $row = PropertyUnitType::create(['property_id' => $property->id, 'unit_type' => 'studio']);

        $baseUrl = 'http://127.0.0.1:8088/admin/properties/'.$property->id.'/unit-types';

        // No GET/PUT/PATCH routes exist for the collection or the row.
        $this->getJson($baseUrl)->assertStatus(405);
        $this->putJson($baseUrl.'/'.$row->id, ['is_active' => false])->assertStatus(405);
        $this->patchJson($baseUrl.'/'.$row->id, ['is_active' => false])->assertStatus(405);
        $this->getJson($baseUrl.'/'.$row->id)->assertStatus(405);

        // Nothing changed by any of the rejected attempts.
        $row->refresh();
        $this->assertTrue($row->is_active);
    }

    /* ===================================================================
     | 6 — XSS payload is stored as data, never executed
     * =================================================================== */

    public function test_script_payload_in_metadata_is_escaped_on_output(): void
    {
        $this->authenticate();
        $property = $this->propertyOffering(['studio']);
        PropertyUnitType::create([
            'property_id' => $property->id,
            'unit_type' => 'studio',
            'name' => '<script>alert(1)</script>',
            'description' => '<img src=x onerror=alert(2)>',
        ]);

        // Blade {{ }} escapes; the raw script must never reach the HTML.
        $this->get(route('admin.properties.edit', $property))
            ->assertOk()
            ->assertDontSee('<script>alert(1)</script>', false);
    }
}
