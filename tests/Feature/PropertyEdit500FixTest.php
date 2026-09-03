<?php

namespace Tests\Feature;

use App\Models\Property;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * Regression coverage for the HTTP 500 on the admin property edit screen.
 *
 * Each test pins one of the failure modes found during diagnosis:
 * missing Geoapify tables, NULL timestamps, double-encoded `prices` JSON,
 * and a scalar `weekend_days` value.
 */
class PropertyEdit500FixTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();

        $role = Role::updateOrCreate(['slug' => 'super-admin'], ['name' => 'Super Admin']);
        $this->user->roles()->syncWithoutDetaching([$role->id => ['model_type' => User::class]]);

        $this->actingAs($this->user);
    }

    public function test_admin_edit_renders_without_property_places_table(): void
    {
        $property = Property::factory()->create();

        // Simulate a production install where the Geoapify migrations
        // (2026_08_28_000001 / 2026_08_28_000002) have not been run.
        Schema::dropIfExists('property_places');
        Schema::dropIfExists('places');
        $this->assertFalse(Schema::hasTable('property_places'));

        $this->get(route('admin.properties.edit', $property))->assertStatus(200);
    }

    public function test_admin_edit_renders_with_null_timestamps(): void
    {
        $property = Property::factory()->create();

        DB::table('properties')
            ->where('id', $property->id)
            ->update(['created_at' => null, 'updated_at' => null]);

        $this->get(route('admin.properties.edit', $property))->assertStatus(200);
    }

    public function test_admin_edit_renders_with_double_encoded_prices(): void
    {
        $property = Property::factory()->create();

        // A double-encoded JSON string decodes to a string, not an array.
        DB::table('properties')
            ->where('id', $property->id)
            ->update(['prices' => json_encode(json_encode(['studio' => ['night_wd' => 500000]]))]);

        $this->get(route('admin.properties.edit', $property))->assertStatus(200);
    }

    public function test_admin_edit_renders_with_scalar_weekend_days(): void
    {
        $property = Property::factory()->create();

        DB::table('properties')
            ->where('id', $property->id)
            ->update(['weekend_days' => json_encode(6)]);

        $property->refresh();
        $this->assertSame([6, 0], $property->weekendDays());

        $this->get(route('admin.properties.edit', $property))->assertStatus(200);
    }
}
