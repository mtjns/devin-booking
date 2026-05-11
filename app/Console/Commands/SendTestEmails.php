<?php

namespace App\Console\Commands;

use Exception;
use App\Models\Booking;
use App\Mail\BookingUpdated;
use App\Mail\PaymentWarning;
use App\Mail\SystemCrashNotice;
use App\Mail\DepositUnderpaid;
use App\Mail\DepositFullReceived;
use App\Mail\BookingCancelledNotPaid;
use App\Mail\BookingCreatedConfirmation;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class SendTestEmails extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:send-test-emails {email : The email address to send the test emails to}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sends one of every system email to the specified address for visual inspection and SMTP verification.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $email = $this->argument('email');
        $this->info("Preparing to send test emails to: {$email}");

        // Force synchronous queue execution for ONLY this command to prevent background worker crashes
        config(['queue.default' => 'sync']);

        // 1. Create a dummy test booking without triggering Observers 
        // (This prevents the Observer from queueing duplicate emails to the background worker)
        $booking = Booking::withoutEvents(function () use ($email) {
            return Booking::create([
                'customer_email' => $email,
                'customer_name' => 'Email Test User',
                'status' => 'pending',
                'start_date' => now()->addYears(2)->addDays(10),
                'end_date' => now()->addYears(2)->addDays(15),
                'total_price' => 15000,
                'deposit_amount' => 50,
                'paid_amount' => 50,
                'graduate_count' => 0,
                'send_confirmation_email' => false,
                'variable_symbol' => 'TEST' . rand(1000, 9999),
            ]);
        });

        $this->info("Created temporary dummy booking #{$booking->id}.");

        // 2. Prepare all the system mailables
        $mailables = [
            new BookingCreatedConfirmation($booking),
            new BookingUpdated($booking),
            new PaymentWarning($booking, 8),
            new BookingCancelledNotPaid($booking),
            new DepositFullReceived($booking),
            new DepositUnderpaid($booking),
            new SystemCrashNotice(
                new Exception('MOCK EXCEPTION: Toto je testovací chyba pro vizuální kontrolu emailu.'),
                'Artisan Command',
                'Testing system mail templates',
                'Check if this email looks correct in your inbox.'
            )
        ];

        // 3. Send them synchronously
        foreach ($mailables as $mailable) {
            $className = class_basename($mailable);
            $this->line("Sending {$className}...");
            Mail::to($email)->send($mailable);
        }

        // 4. Clean up the dummy booking so we don't litter the database
        $this->info("Emails dispatched. Cleaning up temporary booking...");
        $booking->forceDelete();

        $this->info('All test emails sent successfully!');
    }
}
