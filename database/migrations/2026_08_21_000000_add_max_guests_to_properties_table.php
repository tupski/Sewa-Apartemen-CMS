<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tambah kolom max_guests ke tabel properties.
     * max_guests: jumlah maksimal tamu per unit (default 2).
     * Berlaku untuk semua room type di properti tersebut.
     */
    public function up(): void
    {
        Schema::table('properties', function (Blueprint $table) {
            if (!Schema::hasColumn('properties', 'max_guests')) {
                $table->unsignedTinyInteger('max_guests')
                      ->default(2)
                      ->after('max_days')
                      ->comment('Maksimal jumlah tamu per unit. Default 2 dewasa.');
            }
        });
    }

    public function down(): void
    {
        Schema::table('properties', function (Blueprint $table) {
            if (Schema::hasColumn('properties', 'max_guests')) {
                $table->dropColumn('max_guests');
            }
        });
    }
};
