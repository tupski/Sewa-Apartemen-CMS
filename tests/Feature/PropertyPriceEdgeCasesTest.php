<?php

namespace Tests\Feature;

use App\Models\Property;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Phase 6 regression — empty/degenerate pricing states must render the admin
 * property edit screen without errors (the responsive polish touched the
 * surrounding layout; these pin that nothing in the pricing pipeline broke).
 *
 * Adapted from the quinn-qa-engineer QA branch (PropertyPriceEdgeCasesTest),
 * ported to the current test conventions.
 */
class PropertyPriceEdgeCasesTest extends TestCase
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

    public function test_property_with_empty_prices_and_unit_types(): void
    {
        $this->authenticate();

        $property = Property::factory()->create([
            'status' => 'published',
            'prices' => [],
            'unit_types' => [],
        ]);

        $this->assertNull($property->lowestPrice());
        $this->assertNull($property->cheapestNight());
        $this->assertNull($property->lowestPriceToday());
        $this->assertEmpty($property->availableBookingTypes());

        $this->get(route('admin.properties.edit', $property))->assertStatus(200);
    }

    public function test_property_with_partial_prices(): void
    {
        $this->authenticate();

        $property = Property::factory()->create([
            'status' => 'published',
            'unit_types' => ['studio', '1br'],
            'prices' => [
                'studio' => ['night_wd' => 500000],
                '1br' => [],
            ],
            'weekend_days' => [6, 0],
        ]);

        $this->assertSame(500000.0, $property->lowestPrice());
        $this->assertSame(500000.0, $property->cheapestNight());

        $this->get(route('admin.properties.edit', $property))->assertStatus(200);
    }

    public function test_property_with_null_prices(): void
    {
        $this->authenticate();

        $property = Property::factory()->create([
            'status' => 'published',
            'prices' => null,
            'unit_types' => null,
        ]);

        $this->assertNull($property->lowestPrice());
        $this->assertNull($property->cheapestNight());
        $this->assertNull($property->lowestPriceToday());
        $this->assertEmpty($property->availableBookingTypes());

        $this->get(route('admin.properties.edit', $property))->assertStatus(200);
    }
}
