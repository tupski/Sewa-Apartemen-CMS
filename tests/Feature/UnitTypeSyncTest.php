<?php

namespace Tests\Feature;

use App\Models\Property;
use App\Models\PropertyUnitType;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Regression cover for two "only one unit type shows up" defects:
 *
 *  1. Admin Create/Edit — the "Detail Tipe Kamar" editor showed only some of the
 *     ticked types. Metadata rows are created by the introducing migration's
 *     one-off backfill, so a type ticked through the form afterwards never got a
 *     row and was missing from the editor entirely.
 *
 *  2. Frontend card — the type badge rendered `unit_types[0]` only, so a property
 *     offering studio + 1 BR advertised a single type.
 */
class UnitTypeSyncTest extends TestCase
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

    private function storePayload(array $types, array $overrides = []): array
    {
        return array_merge([
            'name' => 'Apartemen Dua Tipe',
            'slug' => 'apartemen-dua-tipe',
            'status' => 'published',
            'unit_types' => $types,
            'weekend_days' => [6, 0],
        ], $overrides);
    }

    /* ===================================================================
     | 1. Metadata rows follow the checkbox grid
     * =================================================================== */

    public function test_creating_a_property_creates_one_metadata_row_per_selected_type(): void
    {
        $this->post(route('admin.properties.store'), $this->storePayload(['studio', '1br']))
            ->assertRedirect();

        $property = Property::where('slug', 'apartemen-dua-tipe')->firstOrFail();

        $rows = PropertyUnitType::where('property_id', $property->id)
            ->orderBy('sort_order')
            ->pluck('unit_type')
            ->all();

        $this->assertSame(['studio', '1br'], $rows, 'Every ticked type must get a metadata row.');
    }

    public function test_ticking_an_extra_type_on_edit_adds_its_metadata_row(): void
    {
        $property = Property::factory()->create([
            'status' => 'published',
            'slug' => 'apartemen-edit-tipe',
            'unit_types' => ['studio'],
        ]);

        // Only the pre-existing type has a row (mirrors a migration backfill).
        PropertyUnitType::create(['property_id' => $property->id, 'unit_type' => 'studio']);

        $this->put(route('admin.properties.update', $property), $this->storePayload(
            ['studio', '1br'],
            ['slug' => 'apartemen-edit-tipe']
        ))->assertRedirect();

        $rows = PropertyUnitType::where('property_id', $property->id)
            ->orderBy('sort_order')
            ->pluck('unit_type')
            ->all();

        $this->assertSame(
            ['studio', '1br'],
            $rows,
            'A newly ticked type must appear in the "Detail Tipe Kamar" editor.'
        );
    }

    public function test_edit_screen_renders_a_card_for_every_offered_type(): void
    {
        $property = Property::factory()->create([
            'status' => 'published',
            'slug' => 'apartemen-render-tipe',
            'unit_types' => ['studio', '1br'],
        ]);

        $this->put(route('admin.properties.update', $property), $this->storePayload(
            ['studio', '1br'],
            ['slug' => 'apartemen-render-tipe']
        ))->assertRedirect();

        $content = $this->get(route('admin.properties.edit', $property))
            ->assertOk()
            ->getContent();

        // Both rows must reach the Alpine manager's initial payload. The JSON is
        // embedded via @js(), so quotes arrive as \u0022 escapes.
        $this->assertStringContainsString('\u0022unit_type\u0022:\u0022studio\u0022', $content);
        $this->assertStringContainsString('\u0022unit_type\u0022:\u00221br\u0022', $content);
    }

    public function test_unticking_a_type_drops_its_metadata_row(): void
    {
        $property = Property::factory()->create([
            'status' => 'published',
            'slug' => 'apartemen-unttick',
            'unit_types' => ['studio', '1br'],
        ]);

        PropertyUnitType::create(['property_id' => $property->id, 'unit_type' => 'studio']);
        PropertyUnitType::create(['property_id' => $property->id, 'unit_type' => '1br']);

        $this->put(route('admin.properties.update', $property), $this->storePayload(
            ['studio'],
            ['slug' => 'apartemen-unttick']
        ))->assertRedirect();

        $this->assertSame(
            ['studio'],
            PropertyUnitType::where('property_id', $property->id)->pluck('unit_type')->all(),
            'A no-longer-offered type must not keep an orphaned editor card.'
        );
    }

    public function test_metadata_content_is_preserved_when_the_type_stays_selected(): void
    {
        $property = Property::factory()->create([
            'status' => 'published',
            'slug' => 'apartemen-preserve',
            'unit_types' => ['studio'],
        ]);

        PropertyUnitType::create([
            'property_id' => $property->id,
            'unit_type' => 'studio',
            'name' => 'Studio Deluxe',
            'max_guests' => 2,
            'size' => 24,
        ]);

        $this->put(route('admin.properties.update', $property), $this->storePayload(
            ['studio'],
            ['slug' => 'apartemen-preserve']
        ))->assertRedirect();

        $row = PropertyUnitType::where('property_id', $property->id)->firstOrFail();
        $this->assertSame('Studio Deluxe', $row->name, 'Re-saving must not clobber admin metadata.');
        $this->assertSame(2, $row->max_guests);
        $this->assertSame(24.0, $row->size);
        $this->assertSame(1, PropertyUnitType::where('property_id', $property->id)->count());
    }

    /* ===================================================================
     | 2. The card badge lists every offered type
     * =================================================================== */

    public function test_card_renders_a_badge_for_every_unit_type(): void
    {
        $property = Property::factory()->create([
            'status' => 'published',
            'slug' => 'apartemen-badge-dua',
            'name' => 'Apartemen Badge Dua',
            'unit_types' => ['studio', '1br'],
        ]);

        // Render the card partial directly: the "Other accommodations" section
        // needs a second property with coordinates, which is irrelevant here —
        // the badge markup is what this test pins.
        $html = view('properties._card', [
            'property' => $property,
            'distance' => 1.7,
            'distanceFrom' => 'Apartemen Induk',
        ])->render();

        // One chip per offered type, in the declared order.
        $this->assertMatchesRegularExpression(
            '/bg-black\/40 backdrop-blur-sm">\s*Studio\s*</',
            $html,
            'The Studio badge must render on the card.'
        );
        $this->assertMatchesRegularExpression(
            '/bg-black\/40 backdrop-blur-sm">\s*1 BR\s*</',
            $html,
            'The 1 BR badge must render too — the card used to show only the first type.'
        );

        $this->assertSame(
            2,
            substr_count($html, 'bg-black/40 backdrop-blur-sm'),
            'Exactly one badge per offered type (no more, no fewer).'
        );
    }

    public function test_card_badge_row_is_capped_with_an_overflow_chip(): void
    {
        $property = Property::factory()->create([
            'status' => 'published',
            'slug' => 'apartemen-badge-banyak',
            'unit_types' => ['studio', '1br', '2br', '3br'],
        ]);

        $html = view('properties._card', ['property' => $property])->render();

        // 3 badges + 1 overflow chip = 4 chips; the 4th type is only in the tooltip.
        $this->assertSame(4, substr_count($html, 'bg-black/40 backdrop-blur-sm'));
        $this->assertMatchesRegularExpression('/>\s*\+1\s*</', $html);
        $this->assertStringContainsString('3 BR', $html, 'The hidden type stays discoverable via the chip tooltip.');
    }
}
