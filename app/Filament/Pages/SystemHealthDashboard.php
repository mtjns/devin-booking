<?php

namespace App\Filament\Pages;

use App\Models\Booking;
use App\Models\ProcessedTransaction;
use Filament\Pages\Page;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

class SystemHealthDashboard extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-heart';

    protected static ?string $navigationLabel = 'Zdraví systému';

    protected ?string $heading = 'Přehled zdraví systému';

    protected static ?string $navigationGroup = 'Správa';

    protected static ?int $navigationSort = 900;

    protected static string $view = 'filament.pages.system-health-dashboard';

    public static function canAccess(): bool
    {
        $user = auth()->user();

        return (bool) $user?->is_super_admin;
    }

    public function getPendingJobsCountProperty(): int
    {
        if (!DB::getSchemaBuilder()->hasTable('jobs')) {
            return 0;
        }

        return (int) DB::table('jobs')->count();
    }

    public function getFailedJobsCountProperty(): int
    {
        if (!DB::getSchemaBuilder()->hasTable('failed_jobs')) {
            return 0;
        }

        return (int) DB::table('failed_jobs')->count();
    }

    public function getLastBackupInfoProperty(): ?array
    {
        if (!Storage::exists('backups')) {
            return null;
        }

        $files = collect(Storage::files('backups'))
            ->filter(fn($path) => str_ends_with($path, '.sql.gz'))
            ->sortDesc()
            ->values();

        if ($files->isEmpty()) {
            return null;
        }

        $latest = $files->first();
        $timestamp = Storage::lastModified($latest);

        return [
            'path' => $latest,
            'last_modified' => $timestamp,
        ];
    }

    public function getDatabaseConnectionStatusProperty(): bool
    {
        try {
            DB::connection()->getPdo();
            return true;
        } catch (\Exception) {
            return false;
        }
    }

    public function getStorageUsageProperty(): ?array
    {
        try {
            $path = storage_path();
            $used = $this->getDirectorySize($path);
            $total = disk_free_space($path) + $used;

            return [
                'used' => $used,
                'total' => $total,
                'used_percent' => round(($used / $total) * 100, 1),
            ];
        } catch (\Exception) {
            return null;
        }
    }

    private function getDirectorySize(string $path): int
    {
        $size = 0;
        try {
            foreach (glob(rtrim($path, '/') . '/*', GLOB_NOSORT) as $each) {
                if (is_file($each)) {
                    $size += filesize($each);
                } elseif (is_dir($each)) {
                    $size += $this->getDirectorySize($each);
                }
            }
        } catch (\Exception) {
            return 0;
        }
        return $size;
    }

    public function getRecentErrorsProperty(): array
    {
        try {
            $logFile = storage_path('logs/laravel.log');
            if (!file_exists($logFile)) {
                return [];
            }

            $lines = array_reverse(file($logFile));
            $errors = [];
            foreach ($lines as $line) {
                if ((stripos($line, ' ERROR ') !== false || stripos($line, ' ALERT ') !== false) && count($errors) < 5) {
                    $errors[] = trim($line);
                }
            }
            return $errors;
        } catch (\Exception) {
            return [];
        }
    }

    public function getFioBankStatusProperty(): array
    {
        $token = config('services.fio_bank.token');
        $isConfigured = !empty($token);

        $lastTransaction = ProcessedTransaction::orderByDesc('created_at')->first();
        $last24HourTransactions = ProcessedTransaction::where('created_at', '>=', Carbon::now()->subDay())->count();
        $last24HourMatched = ProcessedTransaction::where('created_at', '>=', Carbon::now()->subDay())
            ->where('status', 'processed')
            ->count();

        return [
            'configured' => $isConfigured,
            'last_transaction_at' => $lastTransaction?->created_at,
            'transactions_24h' => $last24HourTransactions,
            'matched_24h' => $last24HourMatched,
            'next_run' => $this->getNextScheduleRun('bookings:process-fio-payments'),
        ];
    }

    public function getPendingBookingsProperty(): array
    {
        $pending = Booking::where('status', 'pending')
            ->whereRaw('enforce_payment_deadline = true')
            ->count();

        $atRisk = Booking::where('status', 'pending')
            ->whereRaw('enforce_payment_deadline = true')
            ->where('created_at', '<=', Carbon::now()->subDays(2))
            ->count();

        return [
            'total' => $pending,
            'at_risk' => $atRisk,
        ];
    }

    public function getBookingStatsProperty(): array
    {
        return [
            'total' => Booking::count(),
            'active' => Booking::where('status', '!=', 'cancelled')->count(),
            'pending' => Booking::where('status', 'pending')->count(),
            'deposit_paid' => Booking::where('status', 'deposit_paid')->count(),
            'paid' => Booking::where('status', 'paid')->count(),
        ];
    }

    private function getNextScheduleRun(string $command): ?string
    {
        // This is a simplified version - in production, you might want to parse the cron schedule more robustly
        if ($command === 'bookings:process-fio-payments') {
            $now = Carbon::now();
            $hour = $now->hour;
            $minute = $now->minute;

            // Command runs every 10 minutes from 7-21 (7 AM to 10 PM)
            if ($hour >= 7 && $hour <= 21) {
                $nextMinute = ceil($minute / 10) * 10;
                if ($nextMinute >= 60) {
                    $next = $now->copy()->addHour()->setMinute(0);
                } else {
                    $next = $now->copy()->setMinute($nextMinute);
                }
            } else {
                $next = $now->copy()->hour(7)->minute(0)->addDay();
            }

            return $next->format('H:i');
        }

        return null;
    }
}

