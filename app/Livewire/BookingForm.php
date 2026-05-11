<?php

namespace App\Livewire;

use App\Models\Booking;
use App\Settings\GeneralSettings;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Component;

class BookingForm extends Component
{
    private function formatRetryAfter(int $seconds): string
    {
        if ($seconds < 60) {
            return "{$seconds} s";
        }

        $minutes = intdiv($seconds, 60);
        $remainingSeconds = $seconds % 60;

        if ($remainingSeconds === 0) {
            return "{$minutes} min";
        }

        return "{$minutes} min {$remainingSeconds} s";
    }

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

    public bool $consent_acknowledgement = false;
    public bool $consent_privacy = false;
    public bool $consent_house_rules = false;

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
            'consent_acknowledgement' => ['accepted'],
            'consent_privacy' => ['accepted'],
            'consent_house_rules' => ['accepted'],
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
            'consent_acknowledgement' => 'souhlas s podmínkami',
            'consent_privacy' => 'souhlas se zpracováním osobních údajů',
            'consent_house_rules' => 'souhlas s domácími pravidly',
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
        $this->resetErrorBag('throttle');

        $ip = request()->ip() ?? 'unknown';
        $email = strtolower(trim((string) $this->customer_email));

        // Automatically checks properties against rules()
        // If it fails, a ValidationException is thrown and execution stops here
        $this->validate();

        // Primary rate limit by IP (prevents broad spam).
        $ipKey = 'booking:submit:ip:' . $ip;
        if (RateLimiter::tooManyAttempts($ipKey, 30)) {
            $seconds = RateLimiter::availableIn($ipKey);
            $wait = $this->formatRetryAfter((int) $seconds);
            $this->addError('throttle', "Příliš mnoho pokusů o rezervaci. Zkuste to prosím znovu za {$wait}.");
            return;
        }

        // Secondary limiter by IP+email (prevents repeated targeting of same inbox).
        $emailKey = null;
        if (!empty($email)) {
            $emailKey = 'booking:submit:ip-email:' . $ip . ':' . sha1($email);
            if (RateLimiter::tooManyAttempts($emailKey, 10)) {
                $seconds = RateLimiter::availableIn($emailKey);
                $wait = $this->formatRetryAfter((int) $seconds);
                $this->addError('throttle', "Příliš mnoho pokusů pro tento e-mail. Zkuste to prosím znovu za {$wait}.");
                return;
            }
        }

        // Count only validated submissions (not tied to specific form fields).
        RateLimiter::hit($ipKey, 10 * 60);
        if (!empty($emailKey)) {
            RateLimiter::hit($emailKey, 30 * 60);
        }

        $start = Carbon::parse($this->start_date);
        $end = Carbon::parse($this->end_date);

        $totalRequestedGuests = $this->graduate_count + $this->student_count + $this->child_count + $this->external_count;

        // Prevent zero-guest bookings that pass the min:0 individual field validation
        if ($totalRequestedGuests === 0) {
            $this->addError('graduate_count', 'Musíte uvést alespoň jednoho hosta.');
            return;
        }

        // Serialize booking creation to avoid overlap race conditions across concurrent requests.
        $lock = DB::selectOne("SELECT GET_LOCK('booking_availability_lock', 10) AS acquired");

        if ((int) ($lock->acquired ?? 0) !== 1) {
            $this->addError('start_date', 'Systém je právě vytížen. Zkuste prosím rezervaci odeslat znovu.');
            return;
        }

        try {
            DB::transaction(function () use ($start, $end, $settings, $totalRequestedGuests) {
                // Re-check availability while holding the lock.
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
                $data = $this->only([
                    'graduate_count',
                    'student_count',
                    'child_count',
                    'external_count',
                    'dog_count',
                    'customer_name',
                    'customer_email',
                    'customer_phone',
                    'customer_notes',
                ]);

                $data['start_date'] = $start->toDateString();
                $data['end_date'] = $end->toDateString();
                $data['send_confirmation_email'] = true;
                $data['enforce_payment_deadline'] = true;

                $booking->fill($data);

                $booking->save();
            });
        } finally {
            DB::statement("SELECT RELEASE_LOCK('booking_availability_lock')");
        }

        if ($this->getErrorBag()->isNotEmpty()) {
            return;
        }

        session()->flash('success', 'Rezervace byla úspěšně odeslána.');

        return redirect()->to('/');
    }

    public function render()
    {
        return view('livewire.booking-form');
    }
}