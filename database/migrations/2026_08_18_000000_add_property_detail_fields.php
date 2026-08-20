<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Traveloka-style detail page data:
     * - max_days: max booking duration in days (0/empty = unlimited)
     * - checkin_time / checkout_time: policy times ("14:00", "12:00")
     * - checkin_method: how guests check in (self checkin, meet staff, etc.)
     * - required_documents: documents guests must bring (json array of strings)
     * - nearby_places: manual nearby list for the map section (json):
     *   [{name, category, distance_km, address?}], category in:
     *   Nearby Places | Transportation | Entertainment/Attraction | Others
     */
    public function up(): void
    {
        Schema::table('properties', function (Blueprint $table) {
            if (!Schema::hasColumn('properties', 'max_days')) {
                $table->unsignedInteger('max_days')->nullable()->after('order')
                    ->comment('Maximum booking duration in days. Null = unlimited.');
            }
            if (!Schema::hasColumn('properties', 'checkin_time')) {
                $table->string('checkin_time', 5)->nullable()->after('max_days');
            }
            if (!Schema::hasColumn('properties', 'checkout_time')) {
                $table->string('checkout_time', 5)->nullable()->after('checkin_time');
            }
            if (!Schema::hasColumn('properties', 'checkin_method')) {
                $table->string('checkin_method', 255)->nullable()->after('checkout_time');
            }
            if (!Schema::hasColumn('properties', 'required_documents')) {
                $table->json('required_documents')->nullable()->after('checkin_method');
            }
            if (!Schema::hasColumn('properties', 'nearby_places')) {
                $table->json('nearby_places')->nullable()->after('required_documents');
            }
        });
    }

    public function down(): void
    {
        Schema::table('properties', function (Blueprint $table) {
            $table->dropColumn(['max_days', 'checkin_time', 'checkout_time', 'checkin_method', 'required_documents', 'nearby_places']);
        });
    }
};
