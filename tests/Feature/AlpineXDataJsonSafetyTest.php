<?php

namespace Tests\Feature;

use App\Models\Place;
use App\Models\Property;
use App\Models\PropertyPlace;
use App\Models\PropertyUnitType;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The POI table and unit-type metadata manager pass row data into Alpine via
 * x-data. Inside a double-quoted HTML attribute, @json() emits raw " characters
 * that terminate the attribute early, so Alpine receives a truncated expression
 * ("poiTable([{") and every child binding fails with "rows is not defined" /
 * "filtered is not defined".
 *
 * These views must use @js(), which emits JSON.parse('...') with " escaped as
 * \u0022 — attribute-safe. The partials build their own row arrays from the
 * database, so this test seeds rows containing quotes, ampersands, single
 * quotes, and markup, then asserts each x-data attribute survives intact.
 */
class AlpineXDataJsonSafetyTest extends TestCase
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

    /**
     * Render a partial and return the complete x-data="..." attribute value on
     * the first element whose value starts with $xdataPrefix. Escaped quotes
     * inside the value are honoured. Null when absent — or when the attribute
     * never closes, which is exactly the truncation this suite guards against.
     *
     * @param  array<string, mixed>  $data
     */
    private function xdataValue(string $view, array $data, string $xdataPrefix): ?string
    {
        $html = view($view, $data)->render();

        $search = 'x-data="'.$xdataPrefix;
        $start = strpos($html, $search);
        if ($start === false) {
            return null;
        }
        $start += strlen('x-data="');

        for ($i = $start, $n = strlen($html); $i < $n; $i++) {
            if ($html[$i] === '\\') {
                $i++;

                continue;
            }
            if ($html[$i] === '"') {
                return substr($html, $start, $i - $start);
            }
        }

        return null;
    }

    public function test_poi_table_xdata_survives_hostile_row_data(): void
    {
        $property = Property::factory()->create([
            'status' => 'published',
            'latitude' => -6.2,
            'longitude' => 106.8,
        ]);

        $place = Place::create([
            'geoapify_place_id' => 'gp-hostile-1',
            'name' => 'Kafe "Kutip" & Ko\'pa — <b>bold</b>',
            'category' => 'catering.cafe',
            'lat' => -6.21,
            'lng' => 106.81,
            'address' => 'Jl. Ampersand & Sons No. "7"',
            'raw_category' => 'catering.cafe',
            'fetched_at' => now(),
        ]);

        PropertyPlace::create([
            'property_id' => $property->id,
            'place_id' => $place->id,
            'source' => 'geoapify',
            'distance_m' => 250,
        ]);

        $xdata = $this->xdataValue(
            'admin.properties._nearby-table',
            ['propertyPlaces' => $property->propertyPlaces()->with('place')->get(), 'property' => $property],
            'poiTable('
        );

        $this->assertNotNull($xdata, 'The POI table x-data attribute was not found (or truncated before capture).');
        $this->assertStringEndsWith(')', $xdata, 'The x-data attribute was truncated — raw " in the payload closed it early.');

        $this->assertStringContainsString("JSON.parse('", $xdata, 'Rows must be embedded via @js(), not @json().');
        $this->assertStringContainsString('Kafe', $xdata, 'The row payload must still carry the provider name.');
        // The double quote arrives as an escaped hex sequence, never as a raw "
        // that could close the attribute.
        $this->assertStringNotContainsString('"Kutip"', $xdata, 'Raw " in row data would terminate the attribute.');
    }

    public function test_unit_type_manager_xdata_is_attribute_safe(): void
    {
        $property = Property::factory()->create([
            'status' => 'published',
            'latitude' => -6.2,
            'longitude' => 106.8,
            'unit_types' => ['studio'],
        ]);

        PropertyUnitType::create([
            'property_id' => $property->id,
            'unit_type' => 'studio',
            'name' => 'Studio "Deluxe" & Co',
            'description' => 'Compact studio with a 24" screen.',
            'max_guests' => 2,
            'is_active' => true,
            'sort_order' => 0,
        ]);

        $xdata = $this->xdataValue(
            'admin.properties._unit-types',
            ['property' => $property],
            'unitTypeManager('
        );

        $this->assertNotNull($xdata, 'The unit-type manager x-data attribute was not found (or truncated before capture).');

        $this->assertStringContainsString("JSON.parse('", $xdata, 'Metadata rows must be embedded via @js(), not @json().');
        // The double quote arrives as an escaped hex sequence (layered by
        // JSON.parse quoting), never as a raw " that could close the attribute.
        $this->assertStringNotContainsString('"Deluxe"', $xdata, 'Raw " in metadata would terminate the attribute.');
        $this->assertStringContainsString('Deluxe', $xdata, 'The metadata payload must still carry the display name.');
    }
}
