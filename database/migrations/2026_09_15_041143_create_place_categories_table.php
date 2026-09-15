<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Artivo-managed POI categories.
 *
 * Replaces the hardcoded `Property::NEARBY_CATEGORIES` / `GeoapifyService::POI_GROUPS`
 * lookup with a database-backed catalogue. Each row binds:
 *   - `slug`  : the raw Geoapify place-category key (e.g. `healthcare.hospital`)
 *               — the stable identity used for Places-API filtering, matching
 *               exact-or-child-prefix (e.g. `public_transport.train` also
 *               matches `public_transport.train.station`).
 *   - labels  : admin-editable localized display names (ID + EN).
 *   - icon    : Font Awesome 6 class shown on markers / admin UI.
 *
 * `places.category` keeps storing the slug string (kept intentionally as a
 * denormalized, indexed column so the join is optional and legacy rows never
 * break); the authoritative label comes from this table.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('place_categories', function (Blueprint $table) {
            $table->id();

            // Raw Geoapify category key — unique so sync mapping is unambiguous.
            $table->string('slug', 64)->unique();

            // Localized display names (editable from admin).
            $table->string('name_id', 100);
            $table->string('name_en', 100);

            // Font Awesome 6 icon class for markers / admin UI.
            $table->string('icon', 100)->nullable();

            // Marker color (hex) so the frontend map can theme markers per category.
            $table->string('color', 7)->nullable();

            $table->boolean('is_active')->default(true);
            $table->unsignedSmallInteger('sort_order')->default(0);

            $table->timestamps();

            $table->index('is_active');
            $table->index('sort_order');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('place_categories');
    }
};
