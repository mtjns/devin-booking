<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Settings\GeneralSettings;
use Illuminate\Http\JsonResponse;

class AvailabilityController extends Controller
{
    /**
     * Retrieves all active bookings and global cabin rules for the public frontend.
     * This endpoint feeds the FullCalendar display and the pricing calculator.
     */
    public function index(GeneralSettings $settings): JsonResponse
    {
        // Fetch bookings that are currently active (pending payment or deposit paid)
        // We ignore cancelled bookings and past bookings to optimize the query
        $bookings = Booking::whereIn('status', ['pending', 'deposit_paid'])
            ->where('end_date', '>=', today())
            ->get()
            ->map(function ($booking) {
                // Calculate the total number of guests for this specific booking
                $totalGuests = $booking->graduate_count + $booking->student_count + $booking->child_count + $booking->external_count;

                // Return a simplified array for each booking containing only the necessary public data
                return [
                    'id' => $booking->id,
                    'customer_name' => $booking->customer_name,
                    'start_date' => $booking->start_date->format('Y-m-d'),
                    'end_date' => $booking->end_date->format('Y-m-d'),
                    'reserved_beds' => $totalGuests,
                    'reserve_whole' => $booking->reserve_whole,
                ];
            });

        // Return the combined payload containing the global rules and the active bookings
        return response()->json([
            'cabin_rules' => [
                'bed_capacity' => $settings->bed_capacity,
                'prices' => [
                    'graduate' => $settings->graduate_price,
                    'student' => $settings->student_price,
                    'child' => $settings->child_price,
                    'external' => $settings->external_price,
                    'dog' => $settings->dog_price,
                    'wood' => $settings->wood_price,
                ],
                'deposit_percentage' => $settings->deposit_percentage,
                'pending_window' => $settings->pending_window,
            ],
            'bookings' => $bookings
        ]);
    }
}