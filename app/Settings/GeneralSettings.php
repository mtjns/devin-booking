<?php

namespace App\Settings;

use Spatie\LaravelSettings\Settings;

class GeneralSettings extends Settings
{
    // Strictly defines the available pricing variables as integers
    public int $graduate_price;
    public int $student_price;
    public int $child_price;
    public int $external_price;
    public int $dog_price;
    public int $wood_price;

    public int $bed_capacity;

    public int $pending_window; // Number of days before a pending booking expires
    public int $deposit_percentage; // Percentage of total price required as a deposit
    public string $bank_account_number;
    public string $bank_code;

    // Groups these settings together in the database and cache
    public static function group(): string
    {
        return 'pricing';
    }
}