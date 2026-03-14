<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class FioBankApiClient
{
    private string $token;
    private string $baseUrl = 'https://fioapi.fio.cz/v1/rest';
    private int $requestDelay = 31000; // 31 seconds between requests (Fio API requires this)

    public function __construct()
    {
        $this->token = config('services.fio_bank.token');
    }

    /**
     * Fetch transactions since last download.
     * 
     * Fio Bank API endpoint: /last/{token}/transactions.json
     * This automatically saves a checkpoint on the server after successful fetch.
     * Subsequent calls will only return new transactions since the last successful download.
     * 
     * @return array|null The transaction data or null if request fails
     */
    public function getLastTransactions(): ?array
    {
        try {
            $url = "{$this->baseUrl}/last/{$this->token}/transactions.json";

            Log::channel('bookings')->info("Fetching new transactions from Fio Bank API: {$url}");

            $response = Http::timeout(10)->get($url);

            if (!$response->successful()) {
                Log::channel('bookings')->error("Fio Bank API error: HTTP {$response->status()}", [
                    'response' => $response->body(),
                ]);
                return null;
            }

            $data = $response->json();

            $transactionCount = count($data['transactionList']['transaction'] ?? []);
            Log::channel('bookings')->info("Successfully fetched {$transactionCount} transactions from Fio Bank");

            return $data;
        } catch (\Exception $e) {
            Log::channel('bookings')->error("Fio Bank API exception: {$e->getMessage()}", [
                'exception' => get_class($e),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);
            return null;
        }
    }

    /**
     * Get transactions for a specific date range.
     * 
     * Fio Bank API endpoint: /periods/{token}/{date_from}/{date_to}/transactions.json
     * Does NOT save a checkpoint. Use getLastTransactions() for automatic checkpoint management.
     * 
     * @param string $dateFrom Date in format YYYY-MM-DD
     * @param string $dateTo Date in format YYYY-MM-DD
     * @return array|null The transaction data or null if request fails
     */
    public function getTransactionsByDateRange(string $dateFrom, string $dateTo): ?array
    {
        try {
            $url = "{$this->baseUrl}/periods/{$this->token}/{$dateFrom}/{$dateTo}/transactions.json";

            Log::channel('bookings')->info("Fetching transactions from {$dateFrom} to {$dateTo} from Fio Bank");

            $response = Http::timeout(10)->get($url);

            if (!$response->successful()) {
                Log::channel('bookings')->error("Fio Bank API error for date range: HTTP {$response->status()}");
                return null;
            }

            return $response->json();
        } catch (\Exception $e) {
            Log::channel('bookings')->error("Fio Bank date range fetch exception: {$e->getMessage()}");
            return null;
        }
    }

    /**
     * Get the minimum delay (in milliseconds) required between consecutive API requests.
     * Fio Bank API enforces a 30-second cooldown between requests.
     * 
     * @return int Delay in milliseconds
     */
    public function getRequestDelay(): int
    {
        return $this->requestDelay;
    }

    /**
     * Check if the API token is configured.
     * 
     * @return bool
     */
    public function isConfigured(): bool
    {
        return !empty($this->token);
    }
}
