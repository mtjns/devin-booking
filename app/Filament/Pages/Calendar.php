<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;

class Calendar extends Page
{
    // Sets the icon that appears next to "Calendar" in the sidebar
    protected static ?string $navigationIcon = 'heroicon-o-calendar';

    protected static ?string $navigationLabel = 'Kalendář rezervací';
    protected static ?string $title = 'Kalendář rezervací';

    protected static string $view = 'filament.pages.calendar';

    // Injects the calendar widget into the top of this specific page
    protected function getHeaderWidgets(): array
    {
        return [
            \App\Livewire\BookingCalendarWidget::class,
        ];
    }
}