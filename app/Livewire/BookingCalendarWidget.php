<?php

namespace App\Livewire;

use App\Models\Booking;
use Saade\FilamentFullCalendar\Widgets\FullCalendarWidget;
use Saade\FilamentFullCalendar\Data\EventData;
use Filament\Support\RawJs;

class BookingCalendarWidget extends FullCalendarWidget
{
    // Ensures the calendar stretches across the entire width of the screen
    protected int|string|array $columnSpan = 'full';

    public function config(): array
    {
        return [
            'locale' => 'cs',
            'firstDay' => 1,
            'initialView' => 'dayGridMonth',
            'headerToolbar' => [
                'left' => 'prev,next today',
                'center' => 'title',
                'right' => 'dayGridMonth,listYear',
            ],
            'titleFormat' => [
                'month' => 'long',
                'year' => 'numeric',
            ],
        ];
    }

    public function fetchEvents(array $fetchInfo): array
    {
        return Booking::query()
            // Filters the database query to only load bookings visible in the current month view
            ->where('start_date', '<=', $fetchInfo['end'])
            ->where('end_date', '>=', $fetchInfo['start'])
            ->get()
            ->map(function (Booking $booking) {
                // Calculates the combined guest total across all pricing tiers
                $totalGuests = $booking->graduate_count + $booking->student_count + $booking->child_count + $booking->external_count;

                // Constructs the summary string to be displayed in the browser tooltip
                $hoverText = "{$totalGuests} Guests | {$booking->total_price} CZK | Status: {$booking->status}";

                // Returns a raw array. FullCalendar automatically moves non-standard keys like 'description' into its internal 'extendedProps' object.
                return [
                    'id' => $booking->id,
                    'title' => $booking->customer_name,
                    'start' => $booking->start_date->format('Y-m-d'),
                    'end' => $booking->end_date->format('Y-m-d'),
                    'url' => \App\Filament\Resources\BookingResource::getUrl('edit', ['record' => $booking->id]),
                    'description' => $hoverText,
                ];
            })
            ->toArray();
    }
}