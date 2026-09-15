<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Support the admin listing/search and the property-form amenity picker:
     * both filter on `category` + `is_active` and order by `name`.
     */
    public function up(): void
    {
        Schema::table('amenities', function (Blueprint $table) {
            $table->index(['category', 'is_active', 'name'], 'amenities_category_active_name_idx');
        });
    }

    public function down(): void
    {
        Schema::table('amenities', function (Blueprint $table) {
            $table->dropIndex('amenities_category_active_name_idx');
        });
    }
};
