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
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('units');
    }
};
