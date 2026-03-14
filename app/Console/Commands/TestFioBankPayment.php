<?php

namespace App\Console\Commands;

use App\Models\Booking;
use App\Models\ProcessedTransaction;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class TestFioBankPayment extends Command
{
    protected $signature = 'bookings:test-fio-payment {variableSymbol} {amount : Amount in CZK}';

    protected $description = 'Test payment processing by simulating a Fio Bank transaction with a specific variable symbol and amount';

    public function handle(): int
    {
        $variableSymbol = $this->argument('variableSymbol');
        $amount = (int) $this->argument('amount');
        
        $this->info("Testing Fio Bank payment simulation...");
        $this->line("Variable Symbol: {$variableSymbol}");
        $this->line("Amount: {$amount} Kč");
        $this->newLine();

        // Validate amount
        if ($amount <= 0) {
            $this->error("Error: Amount must be positive (credits only).");
            return 1;
        }

        // Find booking by variable symbol
        $booking = Booking::where('variable_symbol', $variableSymbol)->first();

        if (!$booking) {
            $this->error("Error: No booking found with variable symbol '{$variableSymbol}'");
            $this->line("Hint: Use 'php artisan bookings:list' to see all bookings and their variable symbols.");
            return 1;
        }

        // Show booking information
        $this->info("Found booking:");
        $this->line("  ID: #{$booking->id}");
        $this->line("  Guest names: {$booking->guest_names}");
        $this->line("  Total price: {$booking->total_price} Kč");
        $this->line("  Current paid: {$booking->paid_amount} Kč");
        $this->line("  Status: {$booking->status}");
        $this->newLine();

        // Check if the amount would overpay
        if ($amount <= $booking->paid_amount) {
            $this->warn("Warning: Transaction amount ({$amount} Kč) is not greater than current paid amount ({$booking->paid_amount} Kč).");
            if (!$this->confirm('Continue anyway?')) {
                return 0;
            }
        }

        // Show what will happen
        $difference = $amount - $booking->paid_amount;
        $this->info("This will:");
        $this->line("  Update paid_amount from {$booking->paid_amount} Kč to {$amount} Kč");
        $this->line("  Increase payment by {$difference} Kč");
        
        // Calculate deposit percentage
        $depositPercentage = (int) setting('general_settings.deposit_percentage', 30);
        $requiredDeposit = (int) ($booking->total_price * $depositPercentage / 100);
        
        if ($booking->paid_amount < $requiredDeposit && $amount >= $requiredDeposit) {
            $this->line("  ✅ Status will change to 'deposit_paid' (deposit threshold reached)");
        } elseif ($booking->paid_amount < $booking->total_price && $amount >= $booking->total_price) {
            $this->line("  ✅ Status will change to 'paid' (fully paid)");
        } elseif ($amount > $booking->paid_amount && $amount < $requiredDeposit) {
            $this->line("  ℹ️  Status remains 'pending' (partial payment)");
        }
        
        $this->newLine();

        if (!$this->confirm('Process this test payment?')) {
            $this->line('Cancelled.');
            return 0;
        }

        // Store old data for logging
        $oldAmount = $booking->paid_amount;
        $oldStatus = $booking->status;

        // Process the payment
        $booking->paid_amount = $amount;
        $booking->save(); // Triggers Observer

        // Log the test payment
        $transactionId = 'TEST-' . now()->format('YmdHis');
        ProcessedTransaction::create([
            'transaction_id' => $transactionId,
            'booking_id' => $booking->id,
            'variable_symbol' => $variableSymbol,
            'amount' => $amount,
            'status' => 'processed',
            'notes' => "Test payment: {$oldAmount} Kč → {$amount} Kč (status: {$oldStatus} → {$booking->fresh()->status})",
        ]);

        Log::channel('bookings')->info("Test Fio Bank payment processed", [
            'booking_id' => $booking->id,
            'variable_symbol' => $variableSymbol,
            'previous_amount' => $oldAmount,
            'new_amount' => $amount,
            'difference' => $difference,
            'previous_status' => $oldStatus,
            'new_status' => $booking->fresh()->status,
            'test_transaction_id' => $transactionId,
        ]);

        $this->newLine();
        $this->info('✅ Test payment processed successfully!');
        $this->line("Booking #{$booking->id} now has:");
        $this->line("  Paid: {$amount} Kč");
        $this->line("  Status: {$booking->fresh()->status}");
        
        $this->warn('Note: This was a TEST transaction. It will not be sent to Fio Bank.');
        $this->line("The transaction ID '{$transactionId}' is marked as test in the database.");

        return 0;
    }
}
