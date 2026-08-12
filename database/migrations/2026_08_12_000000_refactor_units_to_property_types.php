<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Replace the Unit entity with property-level unit types & pricing.
     *
     * - Drops: units, amenity_unit
     * - Bookings: unit_id -> booking_type / unit_type / duration_hours / price_breakdown
     * - Properties: adds unit_types / weekend_days / prices (JSON)
     */
    public function up(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            if (Schema::hasColumn('bookings', 'unit_id')) {
                $table->dropForeign(['unit_id']);
                $table->dropColumn('unit_id');
            }

            if (!Schema::hasColumn('bookings', 'booking_type')) {
                $table->string('booking_type')->default('daily')->after('property_id')
                    ->comment('daily, transit, weekly, monthly');
                $table->string('unit_type')->nullable()->after('booking_type');
                $table->unsignedInteger('duration_hours')->nullable()->after('unit_type');
                $table->json('price_breakdown')->nullable()->after('total_price');
            }
        });

        if (!Schema::hasColumn('bookings', 'check_out') || Schema::getColumnType('bookings', 'check_out') !== 'datetime') {
            Schema::table('bookings', function (Blueprint $table) {
                $table->dateTime('check_in')->nullable()->change();
                $table->dateTime('check_out')->nullable()->change();
            });
        }

        Schema::table('properties', function (Blueprint $table) {
            if (!Schema::hasColumn('properties', 'unit_types')) {
                $table->json('unit_types')->nullable()->after('description')
                    ->comment('e.g. ["studio","1br","2br"]');
                $table->json('weekend_days')->nullable()->after('unit_types')
                    ->comment('Days treated as weekend, 0=Sun..6=Sat. Default [6,0]');
                $table->json('prices')->nullable()->after('weekend_days')
                    ->comment('Per-type price grid: night/t3/t6/t9/t12/t24 (wd/we), weekly, monthly');
            }
        });

        // Drop pivots first (they hold FKs to units), then the table itself
        if (Schema::hasTable('amenity_unit')) {
            Schema::dropIfExists('amenity_unit');
        }
        if (Schema::hasTable('units')) {
            Schema::dropIfExists('units');
        }
    }

    public function down(): void
    {
        Schema::create('units', function (Blueprint $table) {
            $table->id();
            $table->foreignId('property_id')->constrained()->onDelete('cascade');
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('unit_type');
            $table->integer('floor')->nullable();
            $table->decimal('size_sqm', 8, 2)->nullable();
            $table->integer('bedrooms')->nullable();
            $table->integer('bathrooms')->nullable();
            $table->text('description')->nullable();
            $table->decimal('price_per_night', 10, 2)->nullable();
            $table->decimal('price_per_month', 10, 2)->nullable();
            $table->decimal('price_per_year', 10, 2)->nullable();
            $table->string('status')->default('available');
            $table->foreignId('featured_image_id')->nullable()->constrained('media')->onDelete('set null');
            $table->timestamps();
        });

        Schema::create('amenity_unit', function (Blueprint $table) {
            $table->id();
            $table->foreignId('amenity_id')->constrained()->onDelete('cascade');
            $table->foreignId('unit_id')->constrained()->onDelete('cascade');
            $table->timestamps();
        });

        Schema::table('properties', function (Blueprint $table) {
            $table->dropColumn(['unit_types', 'weekend_days', 'prices']);
        });

        Schema::table('bookings', function (Blueprint $table) {
            $table->dropColumn(['booking_type', 'unit_type', 'duration_hours', 'price_breakdown']);
            $table->foreignId('unit_id')->nullable()->constrained()->onDelete('cascade');
        });

        Schema::table('bookings', function (Blueprint $table) {
            $table->date('check_in')->nullable()->change();
            $table->date('check_out')->nullable()->change();
        });
    }
};
