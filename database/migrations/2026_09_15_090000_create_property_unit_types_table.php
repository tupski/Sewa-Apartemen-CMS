<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Property unit-type metadata layer.
 *
 * `properties.unit_types` (JSON whitelist keys: studio, 1br, …) remains the
 * CANONICAL identity used by pricing, bookings, and search filters. This table
 * adds structured presentation metadata per (property, unit_type) —
 * UNIQUE(property_id, unit_type) guarantees exactly one metadata row per type
 * actually offered by the property — without creating a second identity.
 *
 * Rows are backfilled from existing `properties.unit_types` so the admin form
 * always has a row to edit; `is_active=false` rows stay out of the frontend.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('property_unit_types', function (Blueprint $table) {
            $table->id();

            $table->foreignId('property_id')->constrained('properties')->cascadeOnDelete();
            // Canonical unit-type key (Property::UNIT_TYPES whitelist) — NOT an id.
            $table->string('unit_type', 30);

            // Presentation metadata (all optional, admin-editable).
            $table->string('name', 100)->nullable()
                ->comment('Display name override, e.g. "Studio Deluxe"');
            $table->text('description')->nullable();
            $table->unsignedTinyInteger('max_guests')->nullable();
            $table->string('bed_configuration', 255)->nullable()
                ->comment('e.g. "1 King Bed + 1 Sofa Bed"');
            $table->decimal('size', 8, 2)->nullable();
            $table->string('size_unit', 10)->default('sqm')->nullable();

            $table->boolean('is_active')->default(true);
            $table->unsignedSmallInteger('sort_order')->default(0);

            $table->timestamps();

            // One metadata row per canonical unit type per property.
            $table->unique(['property_id', 'unit_type']);
            $table->index(['property_id', 'is_active']);
        });

        // Backfill: one metadata row per existing properties.unit_types entry so
        // the admin form always has a row to edit. No pricing/booking data is
        // touched; the JSON column stays the canonical availability source.
        if (Schema::hasColumn('properties', 'unit_types')) {
            DB::table('properties')
                ->whereNotNull('unit_types')
                ->orderBy('id')
                ->chunkById(200, function ($properties): void {
                    foreach ($properties as $index => $property) {
                        $types = json_decode((string) $property->unit_types, true);

                        if (! is_array($types)) {
                            continue;
                        }

                        foreach (array_values($types) as $order => $type) {
                            if (! is_string($type) || $type === '') {
                                continue;
                            }

                            DB::table('property_unit_types')->insertOrIgnore([
                                'property_id' => $property->id,
                                'unit_type' => $type,
                                'is_active' => true,
                                'sort_order' => $order,
                                'created_at' => now(),
                                'updated_at' => now(),
                            ]);
                        }
                    }
                });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('property_unit_types');
    }
};
