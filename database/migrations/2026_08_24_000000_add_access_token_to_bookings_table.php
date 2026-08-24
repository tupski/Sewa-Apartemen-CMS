<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * FIND-001: add a unique random access token for public booking lookup.
     *
     * Existing bookings are backfilled so legacy codes stay resolvable while
     * the sequential code is no longer sufficient to enumerate bookings.
     */
    public function up(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->string('access_token', 64)->nullable()->unique()->after('code');
        });

        DB::table('bookings')->orderBy('id')->each(function ($booking) {
            DB::table('bookings')
                ->where('id', $booking->id)
                ->update(['access_token' => \Illuminate\Support\Str::random(24)]);
        });
    }

    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropUnique(['access_token']);
            $table->dropColumn('access_token');
        });
    }
};
