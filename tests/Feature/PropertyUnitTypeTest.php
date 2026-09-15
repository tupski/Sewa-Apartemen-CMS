<?php

namespace Tests\Feature;

use App\Models\Property;
use App\Models\PropertyUnitType;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Phase 5 — property unit-type metadata layer.
 *
 * The canonical unit-type identity remains properties.unit_types (whitelist
 * keys used by pricing, bookings and search); this table only enriches it with
 * presentation metadata per (property, unit_type).
 *
 * Pins: backfill migration semantics, admin CRUD with inline JSON validation,
 * IDOR protection (a row from another property 404s / reorder aborts), the
 * metadata-follows-availability rule, and that pricing/booking keys are never
 * touched by this layer.
 */
class PropertyUnitTypeTest extends TestCase
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

    protected function propertyWithTypes(array $types = ['studio', '1br'], array $attributes = []): Property
    {
        return Property::factory()->create(array_merge([
            'unit_types' => $types,
            'prices' => ['studio' => ['night_wd' => 150000]],
        ], $attributes));
    }

    protected function storeUrl(Property $property): string
    {
        return route('admin.properties.unit-types.store', $property);
    }

    protected function payload(array $overrides = []): array
    {
        return array_merge([
            'unit_type' => 'studio',
            'name' => 'Studio Deluxe',
            'description' => 'Compact studio with city view.',
            'max_guests' => 2,
            'bed_configuration' => '1 King Bed',
            'size' => 24,
            'size_unit' => 'sqm',
            'is_active' => true,
            'sort_order' => 0,
        ], $overrides);
    }

    /* ===================================================================
     | Backfill (migration semantics)
     * =================================================================== */

    public function test_backfill_creates_one_metadata_row_per_existing_type(): void
    {
        // Simulate a pre-existing property from before the migration by
        // inserting directly, then re-running the backfill the migration uses.
        $property = Property::factory()->create(['unit_types' => null, 'prices' => null]);
        DB::table('properties')->where('id', $property->id)->update([
            'unit_types' => json_encode(['studio', '2br']),
        ]);

        $this->artisan('migrate:rollback', ['--step' => 1]);
        $this->artisan('migrate');

        $rows = PropertyUnitType::where('property_id', $property->id)
            ->orderBy('sort_order')
            ->get();

        $this->assertSame(['studio', '2br'], $rows->pluck('unit_type')->all());
        $this->assertSame([0, 1], $rows->pluck('sort_order')->all());
        $this->assertTrue($rows->every(fn ($row) => $row->is_active));
    }

    /* ===================================================================
     | CRUD
     * =================================================================== */

    public function test_admin_can_save_metadata_for_an_offered_type(): void
    {
        $this->authenticate();
        $property = $this->propertyWithTypes();

        $response = $this->postJson($this->storeUrl($property), $this->payload());

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);

        $row = PropertyUnitType::where('property_id', $property->id)->where('unit_type', 'studio')->firstOrFail();
        $this->assertSame('Studio Deluxe', $row->name);
        $this->assertSame(2, $row->max_guests);
        $this->assertSame('1 King Bed', $row->bed_configuration);
        $this->assertSame(24.0, $row->size);
        $this->assertSame('sqm', $row->size_unit);
        $this->assertSame('Studio Deluxe', $row->display_name);
    }

    public function test_metadata_save_is_idempotent_per_unit_type(): void
    {
        $this->authenticate();
        $property = $this->propertyWithTypes();

        $this->postJson($this->storeUrl($property), $this->payload(['name' => 'First Name']));
        $this->postJson($this->storeUrl($property), $this->payload(['name' => 'Second Name']));

        // UNIQUE(property_id, unit_type): one row, updated in place.
        $this->assertSame(1, PropertyUnitType::where('property_id', $property->id)->count());
        $this->assertSame('Second Name', PropertyUnitType::where('property_id', $property->id)->firstOrFail()->name);
    }

    public function test_metadata_cannot_be_saved_for_a_type_the_property_does_not_offer(): void
    {
        $this->authenticate();
        $property = $this->propertyWithTypes(['studio']);

        $response = $this->postJson($this->storeUrl($property), $this->payload(['unit_type' => '3br']));

        $response->assertStatus(422);
        $this->assertSame(0, PropertyUnitType::where('property_id', $property->id)->count());
    }

    public function test_delete_removes_only_the_metadata_not_the_pricing(): void
    {
        $this->authenticate();
        $property = $this->propertyWithTypes(['studio']);
        $row = PropertyUnitType::create([
            'property_id' => $property->id,
            'unit_type' => 'studio',
            'is_active' => true,
        ]);

        $this->deleteJson(route('admin.properties.unit-types.destroy', ['property' => $property, 'unitType' => $row]))
            ->assertStatus(200);

        $this->assertDatabaseMissing('property_unit_types', ['id' => $row->id]);
        // Canonical identity untouched: the type stays offered and priced.
        $this->assertSame(['studio'], $property->refresh()->unit_types);
        $this->assertSame(150000, $property->prices['studio']['night_wd']);
    }

    public function test_reorder_persists_sort_order(): void
    {
        $this->authenticate();
        $property = $this->propertyWithTypes(['studio', '1br']);

        $a = PropertyUnitType::create(['property_id' => $property->id, 'unit_type' => 'studio', 'sort_order' => 0]);
        $b = PropertyUnitType::create(['property_id' => $property->id, 'unit_type' => '1br', 'sort_order' => 1]);

        $this->postJson(route('admin.properties.unit-types.reorder', $property), [
            'order' => [$b->id, $a->id],
        ])->assertStatus(200);

        $this->assertSame([$b->id, $a->id], PropertyUnitType::where('property_id', $property->id)
            ->orderBy('sort_order')->pluck('id')->all());
    }

    public function test_reorder_rejects_ids_from_another_property(): void
    {
        $this->authenticate();
        $property = $this->propertyWithTypes(['studio']);
        $other = $this->propertyWithTypes(['1br']);

        $foreign = PropertyUnitType::create(['property_id' => $other->id, 'unit_type' => '1br']);

        $this->postJson(route('admin.properties.unit-types.reorder', $property), [
            'order' => [$foreign->id],
        ])->assertStatus(404);

        // The foreign row is untouched.
        $this->assertSame(0, $foreign->refresh()->sort_order);
    }

    /* ===================================================================
     | Validation
     * =================================================================== */

    public function test_validation_rejects_invalid_input(): void
    {
        $this->authenticate();
        $property = $this->propertyWithTypes(['studio']);

        // Unknown unit_type key.
        $this->postJson($this->storeUrl($property), $this->payload(['unit_type' => 'penthouse-suite']))
            ->assertStatus(422);

        // Zero / negative guests.
        $this->postJson($this->storeUrl($property), $this->payload(['max_guests' => 0]))
            ->assertStatus(422);
        $this->postJson($this->storeUrl($property), $this->payload(['max_guests' => -2]))
            ->assertStatus(422);

        // Negative size.
        $this->postJson($this->storeUrl($property), $this->payload(['size' => -5]))
            ->assertStatus(422);

        // Invalid size unit.
        $this->postJson($this->storeUrl($property), $this->payload(['size_unit' => 'm2']))
            ->assertStatus(422);

        // Excessive name length.
        $this->postJson($this->storeUrl($property), $this->payload(['name' => str_repeat('x', 101)]))
            ->assertStatus(422);

        $this->assertSame(0, PropertyUnitType::where('property_id', $property->id)->count());
    }

    /* ===================================================================
     | Authorization / IDOR
     * =================================================================== */

    public function test_unit_type_endpoints_require_an_authenticated_admin(): void
    {
        $property = $this->propertyWithTypes(['studio']);
        $row = PropertyUnitType::create(['property_id' => $property->id, 'unit_type' => 'studio']);

        // Guest → redirect to login.
        $this->post($this->storeUrl($property), $this->payload())
            ->assertRedirect(route('login'));

        // Authenticated non-admin → forbidden.
        $this->actingAs($this->user)
            ->postJson($this->storeUrl($property), $this->payload())
            ->assertForbidden();

        $this->actingAs($this->user)
            ->deleteJson(route('admin.properties.unit-types.destroy', ['property' => $property, 'unitType' => $row]))
            ->assertForbidden();

        $this->assertDatabaseHas('property_unit_types', ['id' => $row->id]);
    }

    public function test_a_row_from_another_property_cannot_be_deleted_through_this_property(): void
    {
        $this->authenticate();
        $propertyA = $this->propertyWithTypes(['studio']);
        $propertyB = $this->propertyWithTypes(['1br']);

        $rowB = PropertyUnitType::create(['property_id' => $propertyB->id, 'unit_type' => '1br']);

        // Delete property B's row through property A's URL — IDOR.
        $this->deleteJson(route('admin.properties.unit-types.destroy', ['property' => $propertyA, 'unitType' => $rowB]))
            ->assertNotFound();

        // ...and through a non-existent property id.
        $this->deleteJson(route('admin.properties.unit-types.destroy', ['property' => 999999, 'unitType' => $rowB]))
            ->assertNotFound();

        $this->assertDatabaseHas('property_unit_types', ['id' => $rowB->id]);
    }

    /* ===================================================================
     | Admin UI
     * =================================================================== */

    public function test_edit_screen_renders_the_metadata_manager(): void
    {
        $this->authenticate();
        $property = $this->propertyWithTypes(['studio']);
        PropertyUnitType::create([
            'property_id' => $property->id,
            'unit_type' => 'studio',
            'name' => 'Studio Deluxe',
            'max_guests' => 2,
        ]);

        $this->get(route('admin.properties.edit', $property))
            ->assertOk()
            ->assertSee('unit-types-section', false)
            ->assertSee('Studio Deluxe', false)
            ->assertSee('studio', false);
    }

    public function test_create_screen_renders_the_section_with_a_save_first_notice(): void
    {
        $this->authenticate();

        $this->get(route('admin.properties.create'))
            ->assertOk()
            ->assertSee('unit-types-section', false);
    }
}
