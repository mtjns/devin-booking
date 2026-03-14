<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('bookings', function (Blueprint $table) {
            // Primary key for the bookings table
            $table->id();

            // Customer contact information
            $table->string('customer_name');
            $table->string('customer_email')->nullable();
            $table->string('customer_phone')->nullable();

            // Email notifications and payment enforcement
            $table->boolean('send_confirmation_email')->default(true);
            $table->boolean('enforce_payment_deadline')->default(true);

            // Reservation dates
            $table->date('start_date');
            $table->date('end_date');

            // Reserve the whole cottage
            $table->boolean('reserve_whole')->default(false);

            // Current state of the booking: pending, deposit_paid, cancelled
            $table->string('status')->default('pending');

            // Guest breakdown for capacity and price calculation
            $table->integer('student_count')->default(0);
            $table->integer('graduate_count')->default(0);
            $table->integer('child_count')->default(0);
            $table->integer('external_count')->default(0);
            $table->integer('dog_count')->default(0);

            // Financial information stored in whole numbers (e.g., CZK)
            $table->integer('total_price');
            $table->integer('paid_amount')->default(0);
            $table->integer('deposit_amount')->default(0);
            $table->string('variable_symbol')->nullable();

            // Automatically manages created_at and updated_at timestamps
            $table->timestamps();
            // Track when the last warning email was sent for this booking, if applicable
            $table->timestamp('last_warning_at')->nullable();

            // Notes
            $table->text('customer_notes')->nullable();
            $table->text('admin_notes')->nullable();
        });
    }

    public function down(): void
    {
        // Removes the table if the migration is rolled back
        Schema::dropIfExists('bookings');
    }
};