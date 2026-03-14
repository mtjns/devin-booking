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
        // Creates the main users table for authentication and authorization
        Schema::create('users', function (Blueprint $table) {
            // Primary key identifier
            $table->id();

            // Standard authentication credentials and profile information
            $table->string('name');
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');

            // Persistent login token
            $table->rememberToken();

            // Master override boolean bypassing all security policy checks
            $table->boolean('is_super_admin')->default(false);

            // Granular permission booleans restricting access to specific system areas
            $table->boolean('can_manage_users')->default(false);
            $table->boolean('can_view_bookings')->default(false);
            $table->boolean('can_edit_bookings')->default(false);
            $table->boolean('can_manage_financials')->default(false);

            // Automatically manages created_at and updated_at timestamps
            $table->timestamps();
        });

        // Creates the table for securely storing temporary password reset tokens
        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        // Creates the table for storing temporary user session data in the database
        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Removes all authentication and session tables if the migration is rolled back
        Schema::dropIfExists('users');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('sessions');
    }
};