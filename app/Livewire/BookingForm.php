<?php

namespace App\Livewire;

use App\Models\Booking;
use App\Settings\GeneralSettings;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Livewire\Component;

class BookingForm extends Component
{
    public ?string $start_date = null;
    public ?string $end_date = null;

    public int $graduate_count = 0;
    public int $student_count = 0;
    public int $child_count = 0;
    public int $external_count = 0;
    public int $dog_count = 0;

    public string $customer_name = '';
    public string $customer_email = '';
    public ?string $customer_phone = null;
    public ?string $customer_notes = null;

    public bool $consent = false;

    protected function rules(): array
    {
        return [
            // Restricts the start date to be no earlier than today and no later than exactly one year from today
            'start_date' => ['required', 'date', 'after_or_equal:today', 'before_or_equal:+1 year'],
            // Restricts the end date to be strictly after the start date and no later than one year and one month from today, accommodating stays that begin near the one-year limit
            'end_date' => ['required', 'date', 'after:start_date', 'before_or_equal:+1 year +1 month'],
            'graduate_count' => ['required', 'integer', 'min:0'],
            'student_count' => ['required', 'integer', 'min:0'],
            'child_count' => ['required', 'integer', 'min:0'],
            'external_count' => ['required', 'integer', 'min:0'],
            'dog_count' => ['required', 'integer', 'min:0'],
            'customer_name' => ['required', 'string', 'max:255'],
            'customer_email' => ['required', 'email', 'max:255'],
            'customer_phone' => ['nullable', 'string', 'max:255'],
            'customer_notes' => ['nullable', 'string'],
            'consent' => ['accepted'],
        ];
    }

    public function submitReservation(GeneralSettings $settings)
    {
        $this->validate();

        $start = Carbon::parse($this->start_date);
        $end = Carbon::parse($this->end_date);
        $nights = $start->diffInDays($end);

        $totalRequestedGuests = $this->graduate_count + $this->student_count + $this->child_count + $this->external_count;

        if ($totalRequestedGuests === 0) {
            $this->addError('graduate_count', 'Musíte uvést alespoň jednoho hosta.');
            return;
        }

        // Fetches bookings that intersect with the requested dates.
        // Interval algebra (StartA < EndB AND EndA > StartB) to detect overlaps.
        $overlappingBookings = Booking::whereIn('status', ['pending', 'deposit_paid'])
            ->where(function ($query) use ($start, $end) {
                $query->where('start_date', '<', $end)
                    ->where('end_date', '>', $start);
            })->get();

        $period = CarbonPeriod::create($start, $end->copy()->subDay());

        foreach ($period as $date) {
            $reservedForNight = 0;

            foreach ($overlappingBookings as $booking) {
                if ($date->between($booking->start_date, $booking->end_date->copy()->subDay())) {

                    if ($booking->reserve_whole) {
                        $this->addError('start_date', 'Zvolený termín koliduje s rezervací celé chaty.');
                        return;
                    }

                    $reservedForNight += ($booking->graduate_count + $booking->student_count + $booking->child_count + $booking->external_count);
                }
            }

            if (($reservedForNight + $totalRequestedGuests) > $settings->bed_capacity) {
                $this->addError('start_date', 'Pro zvolený termín již nezbývá dostatek volných lůžek.');
                return;
            }
        }

        // Calculate nightly guest rate: each guest type × their price
        $nightlyGuestRate = ($this->graduate_count * $settings->graduate_price) +
            ($this->student_count * $settings->student_price) +
            ($this->child_count * $settings->child_price) +
            ($this->external_count * $settings->external_price) +
            ($this->dog_count * $settings->dog_price);

        // Formula: (nightly_guest_rate + wood_fee) × number_of_nights
        // This is pre-calculated here for the public form; Booking model has a fallback for admin panel entries
        $totalPrice = ($nightlyGuestRate + $settings->wood_price) * $nights;

        $booking = new Booking();
        $booking->fill($this->all());
        $booking->total_price = $totalPrice;
        $booking->save();

        session()->flash('success', 'Rezervace byla úspěšně odeslána. Podrobnosti naleznete v e-mailu.');
        return redirect()->route('home');
    }

    public function render()
    {
        return view('livewire.booking-form');
    }
}