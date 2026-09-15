<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Extends `property_places` with admin-controlled presentation overrides and
 * per-mode routed travel metrics (walk / drive / motorcycle).
 *
 * Presentation overrides (custom_name / show_on_frontend) are PRESERVED by the
 * Geoapify sync — the sync only fills measured travel data and the raw place
 * name on the `places` row; it never touches these columns once set.
 *
 * Travel columns are nullable: a mode the router could not measure stays NULL
 * ("not measured") instead of a fabricated zero/straight-line value.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('property_places', function (Blueprint $table) {
            if (! Schema::hasColumn('property_places', 'show_on_frontend')) {
                $table->boolean('show_on_frontend')->default(true)->after('source');
            }

            if (! Schema::hasColumn('property_places', 'custom_name')) {
                $table->string('custom_name', 255)->nullable()->after('show_on_frontend')
                    ->comment('Admin presentation override; provider name stays on places.name');
            }

            if (! Schema::hasColumn('property_places', 'walking_duration_s')) {
                $table->unsignedInteger('walking_distance_m')->nullable();
                $table->unsignedInteger('walking_duration_s')->nullable();
            }

            if (! Schema::hasColumn('property_places', 'driving_duration_s')) {
                $table->unsignedInteger('driving_distance_m')->nullable()
                    ->comment('Routed car distance in metres (Geoapify Route Matrix, mode=drive)');
                $table->unsignedInteger('driving_duration_s')->nullable()
                    ->comment('Routed car duration in seconds (Geoapify Route Matrix, mode=drive)');
            }

            if (! Schema::hasColumn('property_places', 'motorcycle_duration_s')) {
                $table->unsignedInteger('motorcycle_distance_m')->nullable()
                    ->comment('Routed motorcycle distance in metres (Geoapify Route Matrix, mode=motorcycle)');
                $table->unsignedInteger('motorcycle_duration_s')->nullable()
                    ->comment('Routed motorcycle duration in seconds (Geoapify Route Matrix, mode=motorcycle)');
            }
        });

        // Frontend renders only visible POIs; this keeps that filter index-backed
        // together with the existing property_id lookup.
        if (! $this->indexExists('property_places', 'property_places_property_visible_idx')) {
            Schema::table('property_places', function (Blueprint $table) {
                $table->index(['property_id', 'show_on_frontend'], 'property_places_property_visible_idx');
            });
        }
    }

    public function down(): void
    {
        Schema::table('property_places', function (Blueprint $table) {
            if ($this->indexExists('property_places', 'property_places_property_visible_idx')) {
                $table->dropIndex('property_places_property_visible_idx');
            }

            foreach ([
                'motorcycle_duration_s', 'motorcycle_distance_m',
                'driving_duration_s', 'driving_distance_m',
                'custom_name', 'show_on_frontend',
            ] as $column) {
                if (Schema::hasColumn('property_places', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }

    private function indexExists(string $table, string $name): bool
    {
        if (! Schema::hasTable($table)) {
            return false;
        }

        $indexes = Schema::getIndexes($table);

        return collect($indexes)->contains(fn (array $index) => $index['name'] === $name);
    }
};
