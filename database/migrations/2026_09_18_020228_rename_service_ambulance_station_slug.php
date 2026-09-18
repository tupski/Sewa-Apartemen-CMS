<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * service.ambulance_station is not a valid Geoapify Places-API category — the
 * provider rejects it with HTTP 400 ("Category is not supported"), so every POI
 * sync reported "Failed category: service.ambulance_station" and never fetched
 * any ambulance POIs. The valid slug is emergency.ambulance_station (verified
 * against the live API: HTTP 200 with features).
 *
 * Heals installs that already seeded the broken slug by renaming the row in
 * place — the id, localized labels, and any admin edits survive. Fresh installs
 * get the correct slug from PlaceCategorySeeder, which ships the fixed default;
 * seeding data from a migration on an empty catalogue would fight the seeder.
 *
 * The wrong slug never produced a single place (the API rejected it outright),
 * so there are no places rows to migrate.
 */
return new class extends Migration
{
    private const OLD_SLUG = 'service.ambulance_station';

    private const NEW_SLUG = 'emergency.ambulance_station';

    public function up(): void
    {
        if (! Schema::hasTable('place_categories')) {
            return;
        }

        $oldRow = DB::table('place_categories')->where('slug', self::OLD_SLUG)->first();

        if ($oldRow === null) {
            return; // Nothing to heal (already renamed, or a fresh install).
        }

        $newRow = DB::table('place_categories')->where('slug', self::NEW_SLUG)->first();

        if ($newRow === null) {
            // Rename in place: keep the row identity (and admin label edits).
            DB::table('place_categories')
                ->where('id', $oldRow->id)
                ->update(['slug' => self::NEW_SLUG]);
        } else {
            // Both rows exist (the fixed default was re-seeded alongside the
            // stale one). Keep the new row, drop the stale duplicate so the
            // sync cannot pick the dead slug up again.
            DB::table('place_categories')->where('id', $oldRow->id)->delete();
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('place_categories')) {
            return;
        }

        // Restore the previous slug only when this migration performed a pure
        // rename; Geoapify rejects the old slug outright, so this direction
        // exists purely for schema symmetry in pre-sync installs.
        $newRow = DB::table('place_categories')->where('slug', self::NEW_SLUG)->first();

        if ($newRow !== null && ! DB::table('place_categories')->where('slug', self::OLD_SLUG)->exists()) {
            DB::table('place_categories')
                ->where('id', $newRow->id)
                ->update(['slug' => self::OLD_SLUG]);
        }
    }
};
