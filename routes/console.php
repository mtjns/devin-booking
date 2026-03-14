<?php

use Illuminate\Support\Facades\Schedule;

// Executes the pending bookings evaluation at exactly midnight, 8:00 AM, 12:00 PM (noon), and 8:00 PM.
// The cron expression '0 0,8,12,20 * * *' dictates the exact minutes and hours for the task to trigger.
Schedule::command('bookings:process-pending')->cron('0 0,8,12,20 * * *');

// Fetch new Fio Bank transactions daily (if configured).
// This is the only command that should use the 'last' endpoint of Fio Bank API since it maintains a checkpoint.
// It runs once daily at 1:00 AM to avoid conflicts with other background jobs.
// If FIO_BANK_API_TOKEN is not set, this command will gracefully skip.
Schedule::command('bookings:process-fio-payments')
    ->daily()
    ->at('01:00')
    ->onFailure(function () {
        \Illuminate\Support\Facades\Log::channel('bookings')->error('Scheduled Fio Bank payment processing failed - admins should have received an email notification');
    });