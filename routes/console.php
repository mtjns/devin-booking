<?php

use Illuminate\Support\Facades\Schedule;

// Executes the pending bookings evaluation at exactly 0:00, 8:00, 12:00, and 20:00.
Schedule::command('bookings:process-pending')->cron('0 0,8,12,20 * * *');

// Fetch new Fio Bank transactions every 10 minutes from 7:00 AM to 10:00 PM.
// If FIO_BANK_API_TOKEN is not set, this command will gracefully skip.
// If an error occurs, admins are notified via ADMIN_CRASH_EMAILS.
Schedule::command('bookings:process-fio-payments')
    ->cron('*/10 7-21 * * *')
    ->onFailure(function () {
        \Illuminate\Support\Facades\Log::channel('bookings')->error('Scheduled Fio Bank payment processing failed - admins should have received an email notification');
    });