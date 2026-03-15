<?php

namespace App\Console\Commands;

use App\Models\Booking;
use App\Models\ProcessedTransaction;
use App\Services\FioBankApiClient;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class ProcessFioBankPayments extends Command
{
    protected $signature = 'bookings:process-fio-payments {--force : Process without respecting rate limits}';

    protected $description = 'Fetch transactions from Fio Bank API and match them to bookings by variable symbol';

    private FioBankApiClient $fioClient;

    public function __construct()
    {
        parent::__construct();
        $this->fioClient = new FioBankApiClient();
    }

    public function handle(): int
    {
        try {
            // Check if Fio Bank API is configured
            if (!$this->fioClient->isConfigured()) {
                $this->error('Fio Bank API token is not configured. Set FIO_BANK_API_TOKEN in your .env file.');
                Log::channel('bookings')->warning('ProcessFioBankPayments command skipped: FIO_BANK_API_TOKEN not configured');
                return 1;
            }

            $this->info('Fetching transactions from Fio Bank API...');

            // Fetch new transactions since last download
            $data = $this->fioClient->getLastTransactions();

            if ($data === null) {
                $this->error('Failed to fetch transactions from Fio Bank API.');
                Log::channel('bookings')->error('ProcessFioBankPayments: Failed to fetch transactions from Fio Bank API');
                $this->notifyAdminsOfFailure(
                    'Fio Bank API Fetch Failed',
                    action: 'Fetching transactions from Fio Bank API',
                    errorDetails: [
                        'Issue' => 'API call returned null',
                        'Possible Cause' => 'Invalid API token, network error, or Fio Bank API is down',
                        'Next Step' => 'Verify FIO_BANK_API_TOKEN in .env and check Fio Bank API status',
                    ]
                );
                return 1;
            }

        // Check if there are any transactions
        $transactions = $data['transactionList']['transaction'] ?? [];
        
        if (empty($transactions)) {
            $this->info('No new transactions found.');
            return 0;
        }

        $this->info("Processing " . count($transactions) . " transactions...");

        $matched = 0;
        $unmatched = 0;

        foreach ($transactions as $transaction) {
            try {
                // Extract transaction data
                // Column5 = Variable Symbol (VS)
                // Column1 = Amount (Objem) - positive for credits, negative for debits
                // Column0 = Date (Datum)
                // Column2 = Counter-account (Protiúčet)
                // Column22 = Transaction ID
                
                $variableSymbol = trim($transaction['column5']['value'] ?? '');
                $amount = (int) ($transaction['column1']['value'] ?? 0);
                $transactionDate = $transaction['column0']['value'] ?? null;
                $counterAccountNumber = $transaction['column2']['value'] ?? null;
                $transactionId = $transaction['column22']['value'] ?? null;

                // Check if this transaction has already been processed
                $alreadyProcessed = ProcessedTransaction::where('transaction_id', $transactionId)->exists();
                if ($alreadyProcessed) {
                    $this->line("⏭️  Transaction {$transactionId}: Already processed, skipping.");
                    continue;
                }

                // Only process credit transactions (positive amounts - incoming payments)
                if ($amount <= 0) {
                    ProcessedTransaction::create([
                        'transaction_id' => $transactionId,
                        'variable_symbol' => $variableSymbol,
                        'amount' => $amount,
                        'status' => 'skipped',
                        'notes' => 'Non-credit transaction (amount <= 0). Only incoming payments are processed.',
                    ]);
                    $this->line("⏭️  Transaction {$transactionId}: Skipped (non-credit, amount: {$amount} Kč)");
                    continue;
                }

                // Skip if no variable symbol (we can't match it)
                if (empty($variableSymbol)) {
                    ProcessedTransaction::create([
                        'transaction_id' => $transactionId,
                        'variable_symbol' => '',
                        'amount' => $amount,
                        'status' => 'skipped',
                        'notes' => 'No variable symbol - cannot match to booking.',
                    ]);
                    $this->line("❌ Transaction {$transactionId} has no variable symbol, skipping.");
                    $unmatched++;
                    continue;
                }

                // Find booking by variable symbol
                $booking = Booking::where('variable_symbol', $variableSymbol)->first();

                if (!$booking) {
                    ProcessedTransaction::create([
                        'transaction_id' => $transactionId,
                        'variable_symbol' => $variableSymbol,
                        'amount' => $amount,
                        'status' => 'skipped',
                        'notes' => 'No booking found with this variable symbol.',
                    ]);
                    $this->line("❌ No booking found for variable symbol: {$variableSymbol}");
                    Log::channel('bookings')->warning("Fio Bank transaction matched no booking", [
                        'variable_symbol' => $variableSymbol,
                        'amount' => $amount,
                        'transaction_id' => $transactionId,
                    ]);
                    $unmatched++;
                    continue;
                }

                // Check if this transaction represents a new/higher payment (cumulative from bank)
                // The Fio Bank API provides cumulative amounts, so 500 + 3000 = 3500.
                // We skip only if the booking already has this exact amount or more (duplicate/already processed).
                // This allows customers to overpay (pay more than total_price).
                if ($booking->paid_amount >= $amount) {
                    ProcessedTransaction::create([
                        'transaction_id' => $transactionId,
                        'booking_id' => $booking->id,
                        'variable_symbol' => $variableSymbol,
                        'amount' => $amount,
                        'status' => 'skipped',
                        'notes' => "Duplicate or no new payment: booking already has {$booking->paid_amount} Kč, transaction shows {$amount} Kč.",
                    ]);
                    $this->line("⏭️  Booking #{$booking->id} ({$variableSymbol}): Already recorded {$booking->paid_amount} Kč, transaction is {$amount} Kč");
                    continue;
                }

                // Update the booking's paid_amount to the cumulative bank amount.
                // The Observer will automatically trigger status transitions, emails, and deadline checks.
                // Overpayments (amount > total_price) are accepted without any restrictions.
                $oldAmount = $booking->paid_amount;
                $booking->paid_amount = $amount;
                $booking->save(); // Triggers the Observer which handles emails and status transitions
                
                // Record this transaction as processed
                ProcessedTransaction::create([
                    'transaction_id' => $transactionId,
                    'booking_id' => $booking->id,
                    'variable_symbol' => $variableSymbol,
                    'amount' => $amount,
                    'status' => 'processed',
                    'notes' => "Updated booking #{$booking->id} from {$oldAmount} Kč to {$amount} Kč (difference: +{$amount - $oldAmount} Kč)",
                ]);
                
                // Log the successful match
                Log::channel('bookings')->info("Fio Bank payment matched and applied", [
                    'booking_id' => $booking->id,
                    'variable_symbol' => $variableSymbol,
                    'previous_amount' => $oldAmount,
                    'new_amount' => $amount,
                    'difference' => $amount - $oldAmount,
                    'booking_status' => $booking->status,
                    'fio_transaction_id' => $transactionId,
                    'transaction_date' => $transactionDate,
                ]);

                $this->line("✅ Booking #{$booking->id} ({$variableSymbol}): Updated from {$oldAmount} Kč to {$amount} Kč (+{$amount - $oldAmount} Kč)");
                $matched++;
            } catch (\Exception $e) {
                // Extract context about what failed
                $bookingId = $booking?->id ?? null;
                $bookingRef = $booking ? "#{$booking->id} ({$booking->customer_name})" : "(unknown booking)";
                $context = [
                    'transaction_id' => $transactionId ?? 'unknown',
                    'variable_symbol' => $variableSymbol ?? 'unknown',
                    'amount' => $amount ?? 'unknown',
                    'booking_id' => $bookingId,
                ];
                
                Log::channel('bookings')->error("Error processing Fio Bank transaction", array_merge($context, [
                    'exception' => get_class($e),
                    'message' => $e->getMessage(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                ]));
                
                // Notify admins with context
                $this->notifyAdminsOfFailure(
                    'Fio Bank Transaction Processing Error',
                    action: 'Processing payment for booking',
                    booking: $booking,
                    transactionDetails: [
                        'Transaction ID' => $transactionId ?? 'unknown',
                        'Variable Symbol' => $variableSymbol ?? 'unknown',
                        'Amount' => ($amount ?? 'unknown') . ' Kč',
                        'Date' => $transactionDate ?? 'unknown',
                    ],
                    errorDetails: [
                        'Exception Type' => get_class($e),
                        'Error Message' => $e->getMessage(),
                        'File' => $e->getFile(),
                        'Line' => $e->getLine(),
                    ]
                );
                
                $this->error("❌ Error processing transaction {$transactionId} for booking {$bookingRef}: {$e->getMessage()}");
            }
        }

        $this->newLine();
        $this->info("Fio Bank payment processing complete:");
        $this->info("  ✅ Matched and applied: {$matched}");
        $this->info("  ❌ Unmatched: {$unmatched}");

        // Respect Fio Bank's rate limit (30 seconds between requests)
        // unless --force flag is used
        if (!$this->option('force')) {
            $delay = $this->fioClient->getRequestDelay();
            $this->info("Fio Bank API rate limit: waiting {$delay}ms before next request is allowed.");
        }

            return 0;
        } catch (\Exception $e) {
            $message = "Fio Bank payment processing failed with exception: {$e->getMessage()}";
            $this->error($message);
            Log::channel('bookings')->error($message, [
                'exception' => get_class($e),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ]);
            $this->notifyAdminsOfFailure(
                'Fio Bank Payment Processing Failed',
                action: 'Fetching and processing all transactions from Fio Bank API',
                booking: null,
                errorDetails: [
                    'Exception Type' => get_class($e),
                    'Error Message' => $e->getMessage(),
                    'File' => $e->getFile(),
                    'Line' => $e->getLine(),
                ]
            );
            return 1;
        }
    }

    /**
     * Send email notification to admins when payment processing fails.
     * Accepts booking context to provide actionable information.
     */
    private function notifyAdminsOfFailure(
        string $subject,
        string $action = '',
        ?Booking $booking = null,
        array $transactionDetails = [],
        array $errorDetails = []
    ): void {
        $adminEmails = collect(explode(',', config('app.admin_crash_emails', '')))
            ->map(fn ($email) => trim($email))
            ->filter(fn ($email) => !empty($email))
            ->toArray();

        if (empty($adminEmails)) {
            Log::channel('bookings')->warning('No admin emails configured (ADMIN_CRASH_EMAILS). Failure notification not sent.');
            return;
        }

        try {
            $emailBody = "🚨 BOOKING SYSTEM ERROR NOTIFICATION\n";
            $emailBody .= "=" . str_repeat("=", 70) . "\n\n";
            
            $emailBody .= "Error: {$subject}\n";
            $emailBody .= "Time: " . now()->format('Y-m-d H:i:s') . "\n";
            $emailBody .= "Action Being Performed: {$action}\n\n";
            
            if ($booking) {
                $emailBody .= "--- BOOKING DETAILS ---\n";
                $emailBody .= "Booking ID: #{$booking->id}\n";
                $emailBody .= "Customer: {$booking->customer_name}\n";
                $emailBody .= "Email: {$booking->customer_email}\n";
                $emailBody .= "Phone: {$booking->customer_phone}\n";
                $emailBody .= "Date: {$booking->booking_date?->format('Y-m-d H:i')}\n";
                $emailBody .= "Total Price: {$booking->total_price} Kč\n";
                $emailBody .= "Paid Amount: {$booking->paid_amount} Kč\n";
                $emailBody .= "Status: " . strtoupper($booking->status) . "\n";
                $emailBody .= "Variable Symbol: {$booking->variable_symbol}\n\n";
            }
            
            if (!empty($transactionDetails)) {
                $emailBody .= "--- TRANSACTION DETAILS ---\n";
                foreach ($transactionDetails as $key => $value) {
                    $emailBody .= "{$key}: {$value}\n";
                }
                $emailBody .= "\n";
            }
            
            if (!empty($errorDetails)) {
                $emailBody .= "--- ERROR DETAILS ---\n";
                foreach ($errorDetails as $key => $value) {
                    $emailBody .= "{$key}: {$value}\n";
                }
                $emailBody .= "\n";
            }
            
            $emailBody .= "=" . str_repeat("=", 70) . "\n";
            $emailBody .= "Check the system logs for full stack trace and additional details.\n";
            $emailBody .= "Log file: storage/logs/bookings.log\n";

            Mail::raw($emailBody, function ($message) use ($adminEmails, $subject) {
                $message->to($adminEmails)
                    ->subject("[BOOKING ERROR] {$subject}");
            });
            Log::channel('bookings')->info('Failure notification emailed to admins', ['recipients' => $adminEmails]);
        } catch (\Exception $e) {
            Log::channel('bookings')->error('Failed to send admin notification email', [
                'original_error' => "{$subject}",
                'email_error' => $e->getMessage(),
            ]);
        }
