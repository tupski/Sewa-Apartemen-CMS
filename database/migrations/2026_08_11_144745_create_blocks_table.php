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
        Schema::create('blocks', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('type'); // e.g., 'html', 'hero', 'features', 'gallery', 'contact'
            $table->string('identifier')->unique();
            $table->json('content')->nullable();
            $table->integer('order')->default(0);
            $table->string('area')->default('content'); // e.g., 'header', 'content', 'sidebar', 'footer'
            $table->string('status')->default('active'); // 'active', 'inactive'
            $table->json('settings')->nullable();
            $table->json('pages')->nullable(); // Array of page IDs where this block should appear
            $table->timestamps();

            // Indexes for performance
            $table->index('type');
            $table->index('status');
            $table->index('area');
            $table->index('order');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('blocks');
    }
};
