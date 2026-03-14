<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('processed_transactions', function (Blueprint $table) {
            $table->id();
            $table->string('transaction_id')->unique(); // Fio Bank transaction ID (Column22)
            $table->foreignId('booking_id')->nullable()->constrained('bookings')->nullOnDelete();
            $table->string('variable_symbol');
            $table->integer('amount'); // Amount in CZK
            $table->string('status')->default('processed'); // processed, failed, skipped
            $table->text('notes')->nullable(); // Why it was skipped, error messages, etc.
            $table->timestamps();

            // Index for fast lookups
            $table->index('transaction_id');
            $table->index('variable_symbol');
            $table->index('booking_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('processed_transactions');
    }
};
