<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Adds the walking-route metrics produced by the Geoapify Route Matrix API to
 * `property_places`.
 *
 * `distance_m` (already present) keeps its meaning: the straight-line distance
 * between the property and the POI. These two columns hold the ACTUAL walking
 * route results, which are what the 10-minute walking filter is based on.
 *
 * Both columns are nullable: manual POI rows have no walking measurement, and
 * rows synced before this migration stay valid (NULL = "not measured").
 *
 * Every step is guarded with hasColumn() so a partially applied migration can
 * simply be re-run.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('property_places', function (Blueprint $table) {
            if (! Schema::hasColumn('property_places', 'walking_distance_m')) {
                $table->unsignedInteger('walking_distance_m')
                    ->nullable()
                    ->after('distance_m')
                    ->comment('Walking route distance in metres (Geoapify Route Matrix, mode=walk)');
            }

            if (! Schema::hasColumn('property_places', 'walking_duration_s')) {
                $table->unsignedInteger('walking_duration_s')
                    ->nullable()
                    ->after('walking_distance_m')
                    ->comment('Walking route duration in seconds (Geoapify Route Matrix, mode=walk)');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('property_places', function (Blueprint $table) {
            if (Schema::hasColumn('property_places', 'walking_duration_s')) {
                $table->dropColumn('walking_duration_s');
            }

            if (Schema::hasColumn('property_places', 'walking_distance_m')) {
                $table->dropColumn('walking_distance_m');
            }
        });
    }
};
