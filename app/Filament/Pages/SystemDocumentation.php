<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;

class SystemDocumentation extends Page
{
    // Sets the icon displayed in the admin sidebar
    protected static ?string $navigationIcon = 'heroicon-o-book-open';

    // Defines the text label in the sidebar
    protected static ?string $navigationLabel = 'Návod k systému';

    // Sets the title displayed at the top of the page
    protected ?string $heading = 'Příručka administrátora a logika systému';

    // Groups this page under a specific section in the sidebar if desired
    protected static ?string $navigationGroup = 'Správa';

    // Places the guide at the bottom of the navigation group
    protected static ?int $navigationSort = 1000;

    // Points to the Blade template that contains the HTML content
    protected static string $view = 'filament.pages.system-documentation';
}