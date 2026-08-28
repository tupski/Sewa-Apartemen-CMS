<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('places', function (Blueprint $table) {
            $table->id();

            // Geoapify's stable place identifier — used for deduplication on upsert
            $table->string('geoapify_place_id', 128)->nullable()->unique();

            $table->string('name', 255);

            // Must match one of the keys in Property::NEARBY_CATEGORIES
            $table->string('category', 64);

            $table->decimal('lat', 10, 7);
            $table->decimal('lng', 10, 7);

            $table->string('address', 500)->nullable();
            $table->string('website', 500)->nullable();
            $table->string('phone', 50)->nullable();

            // Original Geoapify category string before mapping
            $table->string('raw_category', 255)->nullable();

            // When this POI was last verified/fetched from Geoapify
            $table->timestamp('fetched_at')->nullable();

            $table->timestamps();

            // Index for category-filtered queries
            $table->index('category');

            // Composite index for spatial proximity queries
            $table->index(['lat', 'lng']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('places');
    }
};
