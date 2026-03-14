<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

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
            ->filter(fn ($path) => str_ends_with($path, '.sql.gz'))
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
}

