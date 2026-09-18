<?php

namespace Tests\Feature;

use App\Models\PlaceCategory;
use Database\Seeders\PlaceCategorySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Regression cover for two production defects:
 *
 *  1. Every POI category rendered as a generic fallback on the live site.
 *     place_categories was EMPTY because the table is created by a migration
 *     but populated only by PlaceCategorySeeder (run via db:seed). An install
 *     that ran `php artisan migrate` alone still synced POIs happily —
 *     activeCategorySlugs() falls back to the shipped defaults — so the empty
 *     catalogue was invisible until labels were displayed.
 *
 *  2. An unmapped provider key used to render as the raw dotted slug
 *     (commercial.shopping_mall) and then, after normalization was introduced,
 *     collapsed every unmapped POI into one bucket. Unmapped keys must stay
 *     readable AND distinct.
 */
class PlaceCategoryCatalogueTest extends TestCase
{
    use RefreshDatabase;

    public function test_migrate_alone_populates_the_catalogue(): void
    {
        // RefreshDatabase has already run every migration, which is exactly the
        // unseeded install this test guards: the catalogue must not be empty.
        $this->assertGreaterThan(
            0,
            PlaceCategory::count(),
            'A migrate-only install must end up with a populated category catalogue.'
        );

        // The slugs the synced places in production actually carry.
        $this->assertDatabaseHas('place_categories', ['slug' => 'catering.cafe']);
        $this->assertDatabaseHas('place_categories', ['slug' => 'public_transport']);
    }

    public function test_catalogue_migration_does_not_clobber_admin_edits(): void
    {
        $category = PlaceCategory::where('slug', 'catering.cafe')->firstOrFail();
        $category->update([
            'name_id' => 'Kopi & Kafe',
            'name_en' => 'Coffee & Cafe',
            'sort_order' => 999,
        ]);

        // Re-running the catalogue migration must leave the edited row alone.
        $this->artisan('migrate', ['--path' => 'database/migrations/2026_09_18_171157_seed_place_categories_catalogue.php', '--force' => true])
            ->assertExitCode(0);

        $this->assertSame('Kopi & Kafe', $category->fresh()->name_id);
        $this->assertSame(999, $category->fresh()->sort_order);
        $this->assertSame(1, PlaceCategory::where('slug', 'catering.cafe')->count());
    }

    public function test_synced_provider_slugs_resolve_to_real_labels(): void
    {
        // Verbatim values from the production `places` table.
        $cases = [
            'catering.cafe.coffee_shop' => 'Kafe',
            'catering.cafe' => 'Kafe',
            'public_transport.platform' => 'Transportasi Umum',
            'healthcare.hospital' => 'Rumah Sakit',
            'service.police' => 'Kantor Polisi',
        ];

        foreach ($cases as $slug => $expected) {
            $this->assertSame(
                $expected,
                PlaceCategory::labelForSlug($slug, 'id'),
                "Category [{$slug}] must resolve to its catalogue label, not a fallback."
            );
            $this->assertNotSame(
                PlaceCategory::humanizeSlug($slug),
                PlaceCategory::labelForSlug($slug, 'id'),
                "[{$slug}] must resolve through the catalogue, not the humanized fallback."
            );
        }
    }

    public function test_unmapped_slug_is_humanized_and_never_raw(): void
    {
        // Not in the shipped catalogue.
        $this->assertNull(PlaceCategory::normalizedSlug('commercial.shopping_mall'));

        $label = PlaceCategory::labelForSlug('commercial.shopping_mall', 'id');

        $this->assertSame('Shopping Mall', $label);
        $this->assertStringNotContainsString('.', $label, 'A raw dotted provider key must never reach the UI.');
        $this->assertStringNotContainsString('_', $label, 'Underscores must be humanized away.');
    }

    public function test_unmapped_slugs_keep_distinct_group_keys(): void
    {
        // The previous normalization collapsed every unmapped POI into one
        // bucket, which is why the live page showed a single generic category.
        $keys = [
            PlaceCategory::groupKeyForSlug('commercial.shopping_mall'),
            PlaceCategory::groupKeyForSlug('entertainment.cinema'),
        ];

        $this->assertSame(['Shopping Mall', 'Cinema'], $keys);
        $this->assertCount(2, array_unique($keys), 'Unmapped categories must not merge into one bucket.');

        // A mapped slug still resolves through the catalogue, not humanization.
        $this->assertSame('tourism', PlaceCategory::groupKeyForSlug('tourism.attraction.viewpoint'));
    }

    public function test_seeded_slugs_match_the_shipped_defaults(): void
    {
        $expected = array_column(PlaceCategorySeeder::defaults(), 'slug');
        $actual = PlaceCategory::orderBy('sort_order')->orderBy('id')->pluck('slug')->all();

        $this->assertSame($expected, $actual, 'The catalogue must match the shipped defaults in order.');
    }
}
