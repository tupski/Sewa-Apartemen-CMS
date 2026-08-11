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
        Schema::create('navigations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('parent_id')->nullable();
            $table->string('title');
            $table->text('url')->nullable();
            $table->unsignedBigInteger('page_id')->nullable();
            $table->enum('type', ['page', 'url', 'custom'])->default('page');
            $table->enum('target', ['_self', '_blank'])->default('_self');
            $table->string('icon')->nullable();
            $table->string('menu_location', 100);
            $table->integer('order')->default(0);
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->string('css_class')->nullable();
            $table->timestamps();

            // Indexes
            $table->index('parent_id');
            $table->index('page_id');
            $table->index('menu_location');
            $table->index('order');
            $table->index(['menu_location', 'order']);

            // Foreign keys
            $table->foreign('parent_id')
                ->references('id')
                ->on('navigations')
                ->onDelete('cascade');

            $table->foreign('page_id')
                ->references('id')
                ->on('pages')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('navigations');
    }
};
