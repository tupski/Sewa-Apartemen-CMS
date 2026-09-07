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
        Schema::table('posts', function (Blueprint $table) {
            // Editorial pillar → cluster relationship: a post with a non-null
            // pillar_post_id is a cluster article of that pillar post.
            $table->foreignId('pillar_post_id')
                ->nullable()
                ->after('category_id')
                ->constrained('posts')
                ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('posts', function (Blueprint $table) {
            $table->dropConstrainedForeignId('pillar_post_id');
        });
    }
};
