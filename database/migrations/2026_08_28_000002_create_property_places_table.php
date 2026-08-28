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
        Schema::create('property_places', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('property_id');
            $table->unsignedBigInteger('place_id');

            $table->enum('source', ['manual', 'geoapify'])->default('geoapify');

            // Distance in metres, computed at fetch time
            $table->unsignedInteger('distance_m')->nullable();

            $table->unsignedSmallInteger('sort_order')->default(0);

            $table->timestamps();

            // Foreign key constraints
            $table->foreign('property_id')
                ->references('id')
                ->on('properties')
                ->cascadeOnDelete();

            $table->foreign('place_id')
                ->references('id')
                ->on('places')
                ->cascadeOnDelete();

            // Prevent duplicate pivot rows for the same property+place pair
            $table->unique(['property_id', 'place_id']);

            // Index for common queries scoped to a property
            $table->index('property_id');

            // Index for filtering by source (manual vs geoapify)
            $table->index('source');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('property_places');
    }
};
