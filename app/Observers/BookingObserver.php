<?php

namespace App\Observers;

use App\Models\Booking;
use App\Mail\BookingCreatedConfirmation;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class BookingObserver
{
    public bool $afterCommit = true;

    public function created(Booking $booking): void
    {
        // Created booking notification
        if ($booking->send_confirmation_email && !empty($booking->customer_email)) {
            Mail::to($booking->customer_email)->queue(new BookingCreatedConfirmation($booking));
        }
    }

    public function updated(Booking $booking): void
    {
        // If not notify about changes or empty email skip
        if (!$booking->send_confirmation_email || empty($booking->customer_email)) {
            return;
        }

        // Do not send any update emails for bookings that have already finished
        if ($booking->end_date && $booking->end_date->endOfDay()->isPast()) {
            return;
        }

        // If successfully paid full amount
        if ($booking->wasChanged('status') && $booking->status === 'deposit_paid') {
            Log::channel('bookings')->info("Deposit success email queued for Booking ID: {$booking->id}");
            Mail::to($booking->customer_email)->queue(new \App\Mail\DepositFullReceived($booking));
            return;
        }

        // If paid but not enough (only if still owed after the change)
        if ($booking->wasChanged('paid_amount') && $booking->status === 'pending' && $booking->paid_amount < $booking->deposit_amount) {
            Log::channel('bookings')->info("Underpaid warning email queued for Booking ID: {$booking->id}");
            Mail::to($booking->customer_email)->queue(new \App\Mail\DepositUnderpaid($booking));
            return;
        }

        // If changes made to booking
        if (
            $booking->wasChanged([
                'start_date',
                'end_date',
                'total_price',
                'deposit_amount',
                'graduate_count',
                'student_count',
                'child_count',
                'external_count',
                'dog_count',
                'reserve_whole'
            ])
        ) {
            Log::channel('bookings')->info("General update email queued for Booking ID: {$booking->id}");
            Mail::to($booking->customer_email)->queue(new \App\Mail\BookingUpdated($booking));
        }
    }
}