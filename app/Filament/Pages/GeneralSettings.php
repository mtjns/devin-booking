<?php

namespace App\Filament\Pages;

// Aliases the Spatie settings class to prevent a naming collision with the Filament page class
use App\Settings\GeneralSettings as BlueprintSettings;
use Filament\Actions\Action;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Pages\SettingsPage;

// The class name must exactly match the file name (GeneralSettings.php) for Laravel's autoloader to find it
class GeneralSettings extends SettingsPage
{
    protected static ?string $navigationIcon = 'heroicon-o-cog-6-tooth';

    protected static ?string $navigationLabel = 'Obecné nastavení';
    protected static ?string $title = 'Obecné nastavení';

    protected static ?string $navigationGroup = 'Správa';

    protected static ?int $navigationSort = 100;

    // Binds the visual form directly to the aliased Spatie blueprint class
    protected static string $settings = BlueprintSettings::class;

    // Evaluates access permissions before rendering the page or placing the link in the sidebar
    public static function canAccess(): bool
    {
        return auth()->check();
    }

    // Constructs the form layout and defines the input fields for the settings page
    public function form(Form $form): Form
    {
        $user = auth()->user();
        $readOnly = !$user?->is_super_admin && !$user?->can_manage_financials;

        return $form
            ->schema([
                Forms\Components\Section::make('Cenové úrovně')
                    ->schema([
                        Forms\Components\TextInput::make('graduate_price')
                            ->numeric()
                            ->required()
                            ->suffix('Kč')
                            ->label('Cena absolvent')
                            ->disabled($readOnly),

                        Forms\Components\TextInput::make('student_price')
                            ->numeric()
                            ->required()
                            ->suffix('Kč')
                            ->label('Cena student')
                            ->disabled($readOnly),


                        Forms\Components\TextInput::make('child_price')
                            ->numeric()
                            ->required()
                            ->suffix('Kč')
                            ->label('Cena dítě')
                            ->disabled($readOnly),


                        Forms\Components\TextInput::make('external_price')
                            ->numeric()
                            ->required()
                            ->suffix('Kč')
                            ->label('Cena cizí')
                            ->disabled($readOnly),

                        Forms\Components\TextInput::make('dog_price')
                            ->numeric()
                            ->required()
                            ->suffix('Kč')
                            ->label('Cena pes')
                            ->disabled($readOnly),


                        Forms\Components\TextInput::make('wood_price')
                            ->numeric()
                            ->required()
                            ->suffix('Kč')
                            ->label('Cena dřevo')
                            ->disabled($readOnly),

                    ])->columns(6),

                Forms\Components\Section::make('Bankovní údaje')
                    ->schema([
                        Forms\Components\TextInput::make('bank_account_number')
                            ->required()
                            ->label('Číslo účtu')
                            ->columnSpan(2)
                            ->disabled($readOnly),

                        Forms\Components\TextInput::make('bank_code')
                            ->required()
                            ->label('Kód banky')
                            ->prefix('/')
                            ->disabled($readOnly),
                    ])->columns(3),
                Forms\Components\Section::make('Obecné')
                    ->schema([
                        Forms\Components\TextInput::make('bed_capacity')
                            ->numeric()
                            ->required()
                            ->suffix('lůžek')
                            ->label('Kapacita')
                            ->disabled($readOnly),


                        Forms\Components\TextInput::make('pending_window')
                            ->numeric()
                            ->required()
                            ->suffix('dní')
                            ->label('Okno na zaplacení záruky')
                            ->disabled($readOnly),

                        Forms\Components\TextInput::make('deposit_percentage')
                            ->numeric()
                            ->suffix('%')
                            ->required()
                            ->label('Záruka procenta')
                            ->disabled($readOnly),
                    ])->columns(3),
            ]);
    }

    /**
     * Control which actions (e.g. Save) are shown on the settings form.
     * All admins can view, but only super admins or finance admins can edit.
     *
     * @return array<Action>
     */
    public function getFormActions(): array
    {
        $user = auth()->user();

        if (!$user?->is_super_admin && !$user?->can_manage_financials) {
            // No save button for read-only viewers
            return [];
        }

        return parent::getFormActions();
    }
}