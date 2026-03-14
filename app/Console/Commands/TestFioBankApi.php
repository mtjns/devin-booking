<?php

namespace App\Console\Commands;

use App\Models\Booking;
use App\Services\FioBankApiClient;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class TestFioBankApi extends Command
{
    protected $signature = 'bookings:test-fio-api {--dry-run : Show what would be matched without processing} {--process : Actually process the transactions}';

    protected $description = 'Test Fio Bank API connection and see what transactions exist';

    private FioBankApiClient $fioClient;

    public function __construct()
    {
        parent::__construct();
        $this->fioClient = new FioBankApiClient();
    }

    public function handle(): int
    {
        try {
            $this->info('Testing Fio Bank API connection...');
            $this->newLine();

            // 1. Check if token is configured
            if (!$this->fioClient->isConfigured()) {
                $this->error('❌ Fio Bank API token is NOT configured.');
                $this->line('Set FIO_BANK_API_TOKEN in your .env file.');
                return 1;
            }
            $this->info('✅ Fio Bank API token is configured');
            $this->newLine();

            // 2. Attempt to fetch transactions from the real API
            $this->info('Fetching transactions from Fio Bank API...');
            $data = $this->fioClient->getLastTransactions();

            if ($data === null) {
                $this->error('❌ Failed to fetch transactions from Fio Bank API.');
                $this->warn('This could mean:');
                $this->line('  - Invalid or expired token');
                $this->line('  - Network connectivity issue');
                $this->line('  - Fio Bank API is unavailable');
                Log::channel('bookings')->error('TestFioBankApi: Failed to fetch from API');
                return 1;
            }

            $this->info('✅ Successfully connected to Fio Bank API');
            $this->newLine();

            // 3. Examine the transaction data
            $transactions = $data['transactionList']['transaction'] ?? [];
            $transactionCount = count($transactions);

            if ($transactionCount === 0) {
                $this->info('No new transactions since last checkpoint.');
                $this->line('This is normal if you haven\'t received any payments since the last run.');
                return 0;
            }

            $this->info("Found {$transactionCount} new transaction(s):");
            $this->newLine();

            // 4. Dry-run analysis
            $matched = 0;
            $unmatched = 0;
            $invalidAmount = 0;
            $matches = [];

            foreach ($transactions as $i => $transaction) {
                $variableSymbol = trim($transaction['column5']['value'] ?? '');
                $amount = (int) ($transaction['column1']['value'] ?? 0);
                $date = $transaction['column0']['value'] ?? 'N/A';
                $transactionId = $transaction['column22']['value'] ?? 'N/A';

                $this->line("────────────────────────────────────────");
                $this->line("Transaction #" . ($i + 1) . " | ID: {$transactionId}");
                $this->line("Date: {$date}");
                $this->line("Amount: {$amount} Kč");
                $this->line("Variable Symbol: " . ($variableSymbol ?: '(none)'));

                // Validate
                if ($amount <= 0) {
                    $this->warn("Status: SKIPPED (non-credit, {$amount} Kč)");
                    $invalidAmount++;
                    continue;
                }

                if (empty($variableSymbol)) {
                    $this->warn("Status: SKIPPED (no variable symbol)");
                    $unmatched++;
                    continue;
                }

                // Try to find booking
                $booking = Booking::where('variable_symbol', $variableSymbol)->first();

                if (!$booking) {
                    $this->warn("Status: NOT MATCHED");
                    $this->line("  No booking found with this variable symbol");
                    $unmatched++;
                    continue;
                }

                // Check if it would create a new payment
                if ($booking->paid_amount >= $amount) {
                    $this->info("Status: ALREADY PAID");
                    $this->line("  Booking #{$booking->id} already has {$booking->paid_amount} Kč");
                    continue;
                }

                // Show what would happen
                $difference = $amount - $booking->paid_amount;
                $this->info("Status: ✅ WOULD BE MATCHED");
                $this->line("  Booking ID: #{$booking->id}");
                $this->line("  Guest(s): {$booking->guest_names}");
                $this->line("  Current paid: {$booking->paid_amount} Kč");
                $this->line("  Would update to: {$amount} Kč (+{$difference} Kč)");
                $this->line("  Current status: {$booking->status}");

                // Check if status would change
                $depositPercentage = (int) setting('general_settings.deposit_percentage', 30);
                $requiredDeposit = (int) ($booking->total_price * $depositPercentage / 100);

                if ($booking->paid_amount < $requiredDeposit && $amount >= $requiredDeposit) {
                    $this->line("  ⚡ Would TRIGGER: status → deposit_paid (deposit threshold reached)");
                } elseif ($booking->paid_amount < $booking->total_price && $amount >= $booking->total_price) {
                    $this->line("  ⚡ Would TRIGGER: status → paid (fully paid)");
                }

                $matched++;
                $matches[] = [
                    'id' => $transactionId,
                    'booking_id' => $booking->id,
                    'variable_symbol' => $variableSymbol,
                    'amount' => $amount,
                ];
            }

            $this->line("────────────────────────────────────────");
            $this->newLine();

            // Summary
            $this->info('Summary:');
            $this->line("  ✅ Would match & process: {$matched}");
            $this->line("  ❌ No booking found: {$unmatched}");
            $this->line("  ⏭️  Invalid amounts (≤ 0): {$invalidAmount}");
            $this->newLine();

            // Offer to process
            if ($matched === 0) {
                $this->warn('No transactions would be processed.');
                return 0;
            }

            if ($this->option('process')) {
                if ($this->confirm('⚠️  Actually process these {$matched} transaction(s)?', false)) {
                    $this->info('Processing transactions...');
                    $command = new ProcessFioBankPayments();
                    return $command->handle();
                }
            } else {
                $this->info('Use --process flag to actually process these transactions:');
                $this->line('  php artisan bookings:test-fio-api --process');
            }

            return 0;
        } catch (\Exception $e) {
            $this->error("❌ Error: {$e->getMessage()}");
            Log::channel('bookings')->error('TestFioBankApi exception', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);
            return 1;
        }
    }
}
