<?php

// Handles the pending bookings

namespace App\Console\Commands;

use App\Models\Booking;
use App\Settings\GeneralSettings;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class ProcessPendingBookings extends Command
{
    protected $signature = 'bookings:process-pending';

    protected $description = 'Evaluates pending bookings, sends payment warnings, and cancels expired reservations.';

    public function handle(GeneralSettings $settings): void
    {
        Log::channel('bookings')->info('CRON: Starting background scan for pending bookings.');

        // Only get pending bookings
        $pendingBookings = Booking::where('status', 'pending')->get();

        foreach ($pendingBookings as $booking) {

            // Skip condition
            if (empty($booking->customer_email) || $booking->total_price <= 0 || !$booking->enforce_payment_deadline) {
                continue;
            }

            $expirationDate = $booking->created_at->copy()->addDays($settings->pending_window);
            $daysRemaining = Carbon::now()->diffInDays($expirationDate, false);

            if ($daysRemaining < 0) { // If expired

                Mail::to($booking->customer_email)->queue(new \App\Mail\BookingCancelledNotPaid($booking));

                // Updates the database quietly to prevent the BookingObserver from waking up and sending duplicate general update emails
                $booking->updateQuietly(['status' => 'cancelled']);

                Log::channel('bookings')->info("CRON: Booking ID {$booking->id} expired and was cancelled.");

            } elseif ($daysRemaining >= 0 && $daysRemaining <= 1) { // Last warning (1 day or less remaining)

                // If not warned recently
                if ($booking->last_warning_at === null || $booking->last_warning_at->diffInHours(Carbon::now()) >= 24) {

                    Mail::to($booking->customer_email)->queue(new \App\Mail\PaymentWarning($booking, 1));

                    // Update last warning
                    $booking->updateQuietly(['last_warning_at' => Carbon::now()]);

                    Log::channel('bookings')->info("CRON: Sent 1-day payment warning for Booking ID {$booking->id}.");
                }

            } elseif ($daysRemaining > 1 && $daysRemaining <= 3) { // First warning (2-3 days remaining)

                // If not warned yet
                if ($booking->last_warning_at === null) {

                    Mail::to($booking->customer_email)->queue(new \App\Mail\PaymentWarning($booking, 3));

                    // Update last warning
                    $booking->updateQuietly(['last_warning_at' => Carbon::now()]);

                    Log::channel('bookings')->info("CRON: Sent 3-day payment warning for Booking ID {$booking->id}.");
                }
            }
        }

        Log::channel('bookings')->info('CRON: Background scan completed.');
    }
}