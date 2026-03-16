<?php

namespace App\Models;

use App\Settings\GeneralSettings;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Booking extends Model
{
    // Protects against mass assignment vulnerabilities by specifying exactly which columns can be written to
    protected $fillable = [
        'start_date',
        'end_date',
        'status',
        'customer_name',
        'customer_email',
        'paid_amount',
        'deposit_amount',
        'customer_phone',
        'graduate_count',
        'student_count',
        'child_count',
        'external_count',
        'dog_count',
        'total_price',
        'admin_notes',
        'customer_notes',
        'variable_symbol',
        'reserve_whole',
        'send_confirmation_email',
        'enforce_payment_deadline',
        'last_warning_at'
    ];

    // Instructs Laravel to cast specific database columns into native PHP data types
    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'reserve_whole' => 'boolean',

        'enforce_payment_deadline' => 'boolean',
        'send_confirmation_email' => 'boolean',

        'graduate_count' => 'integer',
        'student_count' => 'integer',
        'child_count' => 'integer',
        'external_count' => 'integer',
        'dog_count' => 'integer',

        'wood_included' => 'boolean',
        'paid_amount' => 'integer',

        'total_price' => 'integer',
        'last_warning_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::saving(function (Booking $booking) {

            $settings = app(\App\Settings\GeneralSettings::class);

            // Variable symbol generation (even for free bookings, for tracking purposes)
            if (empty($booking->variable_symbol)) {
                do {
                    $symbol = date('ym') . mt_rand(1000, 9999);
                } while (static::where('variable_symbol', $symbol)->exists());
                $booking->variable_symbol = $symbol;
            }

            // If the booking has already ended, we consider it historical and skip all update logic to prevent accidental email triggers when modifying old records in the admin panel.
            if ($booking->end_date && $booking->end_date->endOfDay()->isPast()) {
                $booking->send_confirmation_email = false;
                $booking->enforce_payment_deadline = false;
            }

            $depositPercentage = (int) ($settings->deposit_percentage ?? 20);

            // Auto-calculate total price if not already set
            $nights = $booking->start_date->diffInDays($booking->end_date);

            if ($nights > 0) {
                // Calculate nightly guest rate based on guest counts
                $nightlyGuestRate = (
                    ($booking->graduate_count ?? 0) * ($settings->graduate_price ?? 0) +
                    ($booking->student_count ?? 0) * ($settings->student_price ?? 0) +
                    ($booking->child_count ?? 0) * ($settings->child_price ?? 0) +
                    ($booking->external_count ?? 0) * ($settings->external_price ?? 0) +
                    ($booking->dog_count ?? 0) * ($settings->dog_price ?? 0)
                );

                // Total price = (nightly rate + wood price) × nights
                $booking->total_price = (int) (($nightlyGuestRate + ($settings->wood_price ?? 0)) * $nights);
            }


            // Calculates the deposit amount (0% deposit means free booking)
            if (empty($booking->deposit_amount)) {
                $booking->deposit_amount = (int) ($booking->total_price ?? 0) * ($depositPercentage / 100);
            }

            // Status logic: handle free bookings (total_price <= 0) and paid deposits
            if ($booking->total_price <= 0) {
                // Free bookings are automatically marked as paid
                $booking->status = 'deposit_paid';
                $booking->deposit_amount = 0;
                $booking->enforce_payment_deadline = false; // No payment deadline for free bookings
            } elseif ($depositPercentage === 0) {
                // 0% deposit means booking is paid immediately (paid_amount = 0, deposit_amount = 0)
                $booking->status = 'deposit_paid';
                $booking->deposit_amount = 0;
                $booking->enforce_payment_deadline = false;
            } elseif ($booking->paid_amount >= $booking->deposit_amount) {
                // Deposit fully paid
                $booking->status = 'deposit_paid';
            }

            // Empty email => no enforcement
            if (empty($booking->customer_email)) {
                $booking->send_confirmation_email = false;
                $booking->enforce_payment_deadline = false;
            }

            // If paid stop enforcing payment
            if ($booking->status === 'deposit_paid') {
                $booking->enforce_payment_deadline = false;
            }
        });
    }
}