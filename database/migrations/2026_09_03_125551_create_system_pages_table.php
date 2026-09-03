<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Registry of non-CMS routes (homepage, apartment listing, apartment detail
     * template, blog index, contact, promotions) so an admin can attach SEO
     * metadata to them through the existing polymorphic `seo_metadata` morph.
     *
     * This table intentionally stores NO content — only the route identity.
     * Titles/descriptions/Open Graph live on `seo_metadata` via `seoable`, so
     * there is exactly one SEO storage table in the application.
     */
    public function up(): void
    {
        Schema::create('system_pages', function (Blueprint $table) {
            $table->id();
            // Stable machine key, e.g. "home", "properties.index", "properties.show".
            $table->string('key')->unique();
            $table->string('label');
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('system_pages');
    }
};
