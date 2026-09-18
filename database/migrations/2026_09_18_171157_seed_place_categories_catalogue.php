<?php

use Database\Seeders\PlaceCategorySeeder;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Populate the POI category catalogue.
 *
 * The create_place_categories_table migration only creates the table — the
 * rows come from PlaceCategorySeeder, which runs as part of DatabaseSeeder. An
 * install that ran `php artisan migrate` without `db:seed` therefore has an
 * EMPTY catalogue, and every place-category label lookup fails:
 *
 *   - GeoapifyService::activeCategorySlugs() silently falls back to the shipped
 *     defaults, so POI sync still works and `places.category` is populated with
 *     real slugs — the empty catalogue is invisible from the admin side.
 *   - The frontend then has nothing to resolve those slugs against, so labels
 *     degrade (previously to the raw provider slug, now to a generic fallback).
 *
 * This migration makes `php artisan migrate` sufficient: it inserts the shipped
 * defaults idempotently, so a fresh install and an unseeded existing install
 * both end up with the catalogue. It never overwrites a row that already exists
 * (admin edits to labels/icons/colors/order are preserved), and it is a no-op on
 * installs that already seeded.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('place_categories')) {
            return;
        }

        $existing = DB::table('place_categories')->pluck('slug')->all();
        $existing = array_flip($existing);

        $now = now();

        foreach (PlaceCategorySeeder::defaults() as $index => $category) {
            if (isset($existing[$category['slug']])) {
                continue; // Already present (seeded, or admin-created) — leave it alone.
            }

            DB::table('place_categories')->insert([
                'slug' => $category['slug'],
                'name_id' => $category['name_id'],
                'name_en' => $category['name_en'],
                'icon' => $category['icon'],
                'color' => $category['color'],
                'is_active' => true,
                'sort_order' => $index,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    public function down(): void
    {
        // Deliberately not reversible: the catalogue is reference data the app
        // needs to render category labels, and rows may have been edited by an
        // admin or referenced by `places.category`. Dropping them on rollback
        // would re-break the labels this migration exists to fix.
    }
};
