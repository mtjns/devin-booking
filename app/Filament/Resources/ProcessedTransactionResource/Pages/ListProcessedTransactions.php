<?php

namespace App\Filament\Resources\ProcessedTransactionResource\Pages;

use App\Filament\Resources\ProcessedTransactionResource;
use Filament\Resources\Pages\ListRecords;

class ListProcessedTransactions extends ListRecords
{
    protected static string $resource = ProcessedTransactionResource::class;

    protected function getHeaderActions(): array
    {
        // No create action - this is read-only
        return [];
    }
}
