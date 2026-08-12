<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Property photo gallery with categories.
     *
     * - properties.photo_categories: JSON list of gallery categories (defaults + custom)
     * - property_photos: property_id + media_id + category link table
     */
    public function up(): void
    {
        Schema::table('properties', function (Blueprint $table) {
            if (!Schema::hasColumn('properties', 'photo_categories')) {
                $table->json('photo_categories')->nullable()->after('prices');
            }
        });

        Schema::create('property_photos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('property_id')->constrained()->onDelete('cascade');
            $table->foreignId('media_id')->constrained()->onDelete('cascade');
            $table->string('category')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['property_id', 'category']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('property_photos');

        Schema::table('properties', function (Blueprint $table) {
            if (Schema::hasColumn('properties', 'photo_categories')) {
                $table->dropColumn('photo_categories');
            }
        });
    }
};
