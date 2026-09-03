<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Cached Indonesian public holidays / collective leave days.
 *
 * Rows are populated by `php artisan holidays:fetch` (which calls
 * tanggalmerah.upset.dev). The dashboard reads ONLY this table — the
 * API is never hit during a page render.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('national_holidays', function (Blueprint $table) {
            $table->id();
            // One row per (date, name): a single date can carry both a holiday
            // and a collective-leave entry with different names.
            $table->date('date');
            $table->string('name');
            // 'holiday' = Hari Libur Nasional, 'leave' = Cuti Bersama.
            $table->string('type', 20)->default('holiday');
            // Indonesian weekday label as returned by the API ("Kamis").
            $table->string('day', 20)->nullable();
            $table->unsignedSmallInteger('year')->index();
            $table->timestamp('fetched_at')->nullable();
            $table->timestamps();

            $table->unique(['date', 'name']);
            $table->index(['year', 'date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('national_holidays');
    }
};
