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

    /**
     * Defines strict rules for incoming request data.
     * The validate() method automatically evaluates these and halts execution 
     * on failure, preventing invalid state or malicious payloads.
     */
    protected function rules(): array
    {
        return [
            'start_date' => ['required', 'date', 'after_or_equal:today', 'before_or_equal:+1 year +2 days'],
            'end_date' => ['required', 'date', 'after:start_date', 'before_or_equal:+1 year +1 month +2 days'],
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

    /**
     * Mapuje názvy proměnných na uživatelsky přívětivé české názvy pro chybové hlášky.
     * Namísto "customer email je povinný" se zobrazí "E-mail je povinný".
     */
    protected function validationAttributes(): array
    {
        return [
            'start_date' => 'datum příjezdu',
            'end_date' => 'datum odjezdu',
            'graduate_count' => 'počet absolventů',
            'student_count' => 'počet studentů',
            'child_count' => 'počet dětí',
            'external_count' => 'počet externistů',
            'dog_count' => 'počet psů',
            'customer_name' => 'jméno',
            'customer_email' => 'e-mail',
            'customer_phone' => 'telefon',
            'customer_notes' => 'poznámka',
            'consent' => 'souhlas s podmínkami',
        ];
    }

    /**
     * Vlastní texty chybových hlášek pro konkrétní pravidla.
     */
    protected function messages(): array
    {
        return [
            'customer_email.email' => 'Zadejte prosím e-mail v platném formátu.',
            'customer_name.required' => 'Vyplnění jména je povinné.',
            'customer_email.required' => 'Vyplnění e-mailu je povinné.',
            'consent.accepted' => 'Pro odeslání musíte souhlasit s podmínkami.',
            'start_date.after_or_equal' => 'Datum příjezdu nesmí být v minulosti.',
        ];
    }

    public function submitReservation(GeneralSettings $settings)
    {
        // Automatically checks properties against rules()
        // If it fails, a ValidationException is thrown and execution stops here
        $this->validate();

        $start = Carbon::parse($this->start_date);
        $end = Carbon::parse($this->end_date);
        $nights = $start->diffInDays($end);

        $totalRequestedGuests = $this->graduate_count + $this->student_count + $this->child_count + $this->external_count;

        // Prevent zero-guest bookings that pass the min:0 individual field validation
        if ($totalRequestedGuests === 0) {
            $this->addError('graduate_count', 'Musíte uvést alespoň jednoho hosta.');
            return;
        }

        // Queries the database securely using Eloquent, preventing SQL injection
        // Checks for overlapping reservations based on start and end dates
        $overlappingBookings = Booking::whereIn('status', ['pending', 'deposit_paid'])
            ->where(function ($query) use ($start, $end) {
                $query->where('start_date', '<', $end)
                    ->where('end_date', '>', $start);
            })->get();

        $period = CarbonPeriod::create($start, $end->copy()->subDay());

        // Verify capacity on a night-by-night basis
        foreach ($period as $date) {
            $reservedForNight = 0;

            foreach ($overlappingBookings as $booking) {
                if ($date->between($booking->start_date, $booking->end_date->copy()->subDay())) {

                    // If a colliding booking blocks the entire cabin, abort immediately
                    if ($booking->reserve_whole) {
                        $this->addError('start_date', 'Zvolený termín koliduje s rezervací celé chaty.');
                        return;
                    }

                    $reservedForNight += ($booking->graduate_count + $booking->student_count + $booking->child_count + $booking->external_count);
                }
            }

            // Ensure the cumulative guest count does not exceed the cabin's hard limit
            if (($reservedForNight + $totalRequestedGuests) > $settings->bed_capacity) {
                $this->addError('start_date', 'Pro zvolený termín již nezbývá dostatek volných lůžek.');
                return;
            }
        }

        // Construct a new Eloquent model instance using sanitized validated data
        // Price is calculated in the model
        $booking = new Booking();
        $booking->fill([
            'start_date' => $start->toDateString(),
            'end_date' => $end->toDateString(),
            'graduate_count' => $this->graduate_count,
            'student_count' => $this->student_count,
            'child_count' => $this->child_count,
            'external_count' => $this->external_count,
            'dog_count' => $this->dog_count,
            'customer_name' => $this->customer_name,
            'customer_email' => $this->customer_email,
            'customer_phone' => $this->customer_phone,
            'customer_notes' => $this->customer_notes,
        ]);

        // Commits the transaction securely
        $booking->save();

        session()->flash('success', 'Rezervace byla úspěšně odeslána.');

        return redirect()->to('/');
    }

    public function render()
    {
        return view('livewire.booking-form');
    }
}