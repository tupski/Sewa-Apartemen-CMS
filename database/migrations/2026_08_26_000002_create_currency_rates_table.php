<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('currency_rates', function (Blueprint $table) {
            $table->id();
            $table->string('from_currency', 10)->default('IDR');
            $table->string('to_currency', 10);
            $table->decimal('rate', 18, 6);
            $table->string('source', 50)->nullable(); // 'exchangerate-api', 'fixer', 'manual'
            $table->timestamp('fetched_at')->nullable();
            $table->timestamps();
            $table->unique(['from_currency', 'to_currency']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('currency_rates');
    }
};
