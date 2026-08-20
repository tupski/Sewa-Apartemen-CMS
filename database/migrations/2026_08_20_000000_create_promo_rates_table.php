<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('promo_rates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('property_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('applies_to')->default('all'); // 'weekday', 'weekend', 'all', 'custom'
            $table->json('active_days')->nullable();       // e.g. [1,2,3,4] for Mon-Thu
            $table->time('start_time');
            $table->time('end_time');
            $table->unsignedBigInteger('price');
            $table->string('booking_type')->default('all'); // 'night','transit','weekly','monthly','all'
            $table->unsignedInteger('duration_hours')->nullable(); // for transit: 3,6,9,12,24
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('promo_rates');
    }
};
