<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration {
    public function up(): void
    {
        // Initializes the default prices. These can be immediately changed via the Filament dashboard.
        $this->migrator->add('pricing.graduate_price', 100);
        $this->migrator->add('pricing.student_price', 120);
        $this->migrator->add('pricing.child_price', 50);
        $this->migrator->add('pricing.external_price', 200);
        $this->migrator->add('pricing.dog_price', 50);
        $this->migrator->add('pricing.wood_price', 3000);

        // Deposit and pending
        $this->migrator->add('pricing.pending_window', 14); // Default pending window of 14 days
        $this->migrator->add('pricing.deposit_percentage', 20); // Default deposit percentage of 20%

        // Capacity
        $this->migrator->add('pricing.bed_capacity', 30);

        // Bank information
        $this->migrator->add('pricing.bank_account_number', '');
        $this->migrator->add('pricing.bank_code', '');
    }
};