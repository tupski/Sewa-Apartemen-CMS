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
        Schema::create('bookings', function (Blueprint $table) {
            $table->id();

            // Foreign Keys
            $table->foreignId('unit_id')->constrained()->onDelete('cascade');
            $table->foreignId('property_id')->constrained()->onDelete('cascade');

            // Customer Information
            $table->string('customer_name');
            $table->string('customer_email')->nullable();
            $table->string('customer_phone');
            $table->string('customer_whatsapp')->nullable();

            // Booking Dates
            $table->date('check_in');
            $table->date('check_out');
            $table->integer('guests')->default(1);

            // Booking Details
            $table->string('code')->unique()->comment('Booking code like BK-20260811-0001');
            $table->text('message')->nullable()->comment('Customer message or special request');

            // Status
            $table->enum('status', ['pending', 'confirmed', 'cancelled', 'completed'])->default('pending');

            // WhatsApp Integration
            $table->string('whatsapp_status')->default('pending')->comment('whatsapp sent, delivered, read');
            $table->timestamp('whatsapp_sent_at')->nullable();

            // Pricing (cached at booking time)
            $table->decimal('total_price', 15, 2)->default(0);
            $table->decimal('deposit_amount', 15, 2)->default(0);

            // Metadata
            $table->json('metadata')->nullable()->comment('Additional booking data like room preferences');

            // Soft Deletes
            $table->softDeletes();

            // Timestamps
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bookings');
    }
};
