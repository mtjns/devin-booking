<?php

namespace App\Filament\Resources;

use App\Filament\Resources\BookingResource\Pages;
use App\Filament\Resources\BookingResource\RelationManagers;
use App\Models\Booking;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class BookingResource extends Resource
{
    protected static ?string $model = Booking::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    // Sets the plural and singular names for the resource in the navigation menu
    protected static ?string $modelLabel = 'Rezervace';
    protected static ?string $pluralModelLabel = 'Rezervace';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                // Customer contact details
                \Filament\Forms\Components\Section::make('Informace o hostovi')
                    ->schema([
                        \Filament\Forms\Components\TextInput::make('customer_name')
                            ->label('Jméno a příjmení')
                            ->required()
                            ->maxLength(255),

                        \Filament\Forms\Components\TextInput::make('customer_email')
                            ->label('E-mailová adresa')
                            ->email()
                            ->maxLength(255)
                            ->live(onBlur: true)
                            ->afterStateUpdated(function ($state, Forms\Set $set) {
                                if (empty($state)) {
                                    $set('send_confirmation_email', false);
                                    $set('enforce_payment_deadline', false);
                                }
                            }),

                        \Filament\Forms\Components\TextInput::make('customer_phone')
                            ->label('Telefonní číslo')
                            ->tel()
                            ->maxLength(255),
                    ])->columns(3),

                // Core scheduling and current state of the reservation
                \Filament\Forms\Components\Section::make('Termín a stav rezervace')
                    ->schema([
                        \Filament\Forms\Components\DatePicker::make('start_date')
                            ->label('Datum příjezdu')
                            ->required()
                            ->native(false)
                            ->displayFormat('d.m.Y'),

                        // Ensures the end date cannot be set before the start date
                        \Filament\Forms\Components\DatePicker::make('end_date')
                            ->label('Datum odjezdu')
                            ->required()
                            ->native(false)
                            ->displayFormat('d.m.Y')
                            ->afterOrEqual('start_date')
                            ->live(onBlur: true)
                            ->afterStateUpdated(function ($state, Forms\Set $set) {
                                if (!empty($state) && \Carbon\Carbon::parse($state)->endOfDay()->isPast()) {
                                    $set('send_confirmation_email', false);
                                    $set('enforce_payment_deadline', false);
                                }
                            }),

                        Forms\Components\Select::make('status')
                            ->label('Stav')
                            ->options([
                                'pending' => 'Nezaplaceno',
                                'deposit_paid' => 'Záloha zaplacena',
                                'cancelled' => 'Zrušeno',
                            ])
                            ->required()
                            ->default('pending'),
                    ])->columns(3),

                // Manages the specific guest metrics required for capacity and pricing calculations
                \Filament\Forms\Components\Section::make('Rozpis hostů')
                    ->schema([
                        \Filament\Forms\Components\TextInput::make('graduate_count')
                            ->label('Absolventi')
                            ->numeric()
                            ->default(0)
                            ->minValue(0),

                        \Filament\Forms\Components\TextInput::make('student_count')
                            ->label('Studenti')
                            ->numeric()
                            ->default(0)
                            ->minValue(0),

                        \Filament\Forms\Components\TextInput::make('child_count')
                            ->label('Děti')
                            ->numeric()
                            ->default(0)
                            ->minValue(0),

                        \Filament\Forms\Components\TextInput::make('external_count')
                            ->label('Externisté')
                            ->numeric()
                            ->default(0)
                            ->minValue(0),
                        \Filament\Forms\Components\TextInput::make('dog_count')
                            ->label('Psi')
                            ->numeric()
                            ->default(0)
                            ->minValue(0),
                        \Filament\Forms\Components\Toggle::make('reserve_whole')
                            ->label('Rezervovat celou chatu')
                            ->helperText('Zabrání dalším hostům v rezervaci překrývajících se termínů bez ohledu na volnou kapacitu lůžek.')
                            ->default(false),
                    ])->columns(6),

                // Secures the financial identifiers and internal communication
                \Filament\Forms\Components\Section::make('Finance')
                    ->schema([
                        \Filament\Forms\Components\TextInput::make('total_price')
                            ->label('Celková cena')
                            ->numeric()
                            ->minValue(0)
                            ->suffix('Kč')
                            ->placeholder('Vypočítáno automaticky')
                            ->nullable()
                            ->helperText('Ponechte prázdné pro automatický výpočet při uložení rezervace.')
                            ->live(onBlur: true)
                            ->afterStateUpdated(function (\Filament\Forms\Set $set, $state) {
                                if ($state === "0" || $state === 0) {
                                    $set('status', 'deposit_paid');

                                    \Filament\Notifications\Notification::make()
                                        ->title('Bezplatná rezervace')
                                        ->body('Cena byla nastavena na 0 Kč. Tato rezervace bude automaticky označena jako "Záloha zaplacena" a nebude u ní sledována splatnost.')
                                        ->warning()
                                        ->persistent()
                                        ->send();
                                }
                            }),

                        \Filament\Forms\Components\TextInput::make('deposit_amount')
                            ->label('Výše zálohy v Kč')
                            ->numeric()
                            ->minValue(0)
                            ->suffix('Kč')
                            ->nullable()
                            ->placeholder('Generováno automaticky')
                            ->helperText('Ponechte prázdné pro automatický výpočet při uložení rezervace.')
                            ->helperText(function () {
                                $settings = app(\App\Settings\GeneralSettings::class);
                                $percentage = $settings->deposit_percentage ?? 0;
                                return new \Illuminate\Support\HtmlString("Ponechte prázdné pro automatický výpočet při uložení rezervace<br>(<strong>{$percentage}%</strong> z celkové ceny).");
                            }),

                        \Filament\Forms\Components\TextInput::make('paid_amount')
                            ->label('Přijatá částka')
                            ->numeric()
                            ->minValue(0)
                            ->suffix('Kč')
                            ->default(0)
                            ->helperText("Už přijatá částka."),

                        \Filament\Forms\Components\TextInput::make('variable_symbol')
                            ->label('Variabilní symbol')
                            ->nullable()
                            ->unique(ignoreRecord: true)
                            ->placeholder('Generováno automaticky')
                            ->helperText('Ponechte prázdné pro automatické vygenerování při uložení rezervace.'),
                    ])->columns(4),

                Forms\Components\Section::make('Poznámky')
                    ->schema([
                        // Textareas span the full width of the section to allow for longer paragraphs
                        \Filament\Forms\Components\Textarea::make('admin_notes')
                            ->label('Interní poznámky administrátora')
                            ->columnSpanFull(),

                        \Filament\Forms\Components\Textarea::make('customer_notes')
                            ->label('Poznámky zákazníka')
                            ->columnSpanFull(),
                    ]),

                // Controls the automated communication settings for this booking, allowing administrators to disable emails or payment enforcement on a per-booking basis
                Forms\Components\Section::make('Komunikace a automatizace')
                    ->schema([
                        Forms\Components\Toggle::make('send_confirmation_email')
                            ->label('Odeslat e-mail při uložení/úpravě')
                            ->helperText(function (Forms\Get $get) {
                                if (!empty($get('end_date')) && \Carbon\Carbon::parse($get('end_date'))->endOfDay()->isPast()) {
                                    return new \Illuminate\Support\HtmlString('<span style="color: #dc2626;">U historických rezervací nelze odesílat notifikace.</span>');
                                } else if (empty($get('customer_email'))) {
                                    return new \Illuminate\Support\HtmlString('<span style="color: #dc2626;">Zadejte e-mail zákazníka pro možnost odesílat notifikace.</span>');
                                }
                                return 'Okamžitě odešle e-mail zákazníkovi při vytvoření nebo úpravě této rezervace.';
                            })
                            ->default(false)
                            ->disabled(fn(Forms\Get $get): bool => empty($get('customer_email')) || (!empty($get('end_date')) && \Carbon\Carbon::parse($get('end_date'))->endOfDay()->isPast()))
                            ->dehydrated(),

                        Forms\Components\Toggle::make('enforce_payment_deadline')
                            ->label('Vymáhat automatické termíny plateb')
                            ->helperText(function (Forms\Get $get) {
                                if (!empty($get('end_date')) && \Carbon\Carbon::parse($get('end_date'))->endOfDay()->isPast()) {
                                    return new \Illuminate\Support\HtmlString('<span style="color: #dc2626;">U historických rezervací nelze vymáhat platby.</span>');
                                } else if (empty($get('customer_email'))) {
                                    return new \Illuminate\Support\HtmlString('<span style="color: #dc2626;">Zadejte e-mail zákazníka pro možnost vymáhat termíny plateb.</span>');
                                }
                                return 'Automaticky odesílá upozornění na platbu a zruší rezervaci při nezaplacení.';
                            })
                            ->default(false)
                            ->disabled(fn(Forms\Get $get): bool => empty($get('customer_email')) || $get('total_price') <= 0 || (!empty($get('end_date')) && \Carbon\Carbon::parse($get('end_date'))->endOfDay()->isPast()))
                            ->dehydrated(),
                    ])->columns(1),
                Forms\Components\DatePicker::make('last_warning_at')
                    ->displayFormat('d.m.Y')
                    ->label('Naposledy upozorněno na platbu')
                    ->helperText('! Změna může něco rozbít !'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                // Displays the start date formatted to a standard European layout
                \Filament\Tables\Columns\TextColumn::make('start_date')
                    ->label('Příjezd')
                    ->date('d.m.Y')
                    ->sortable(),

                // Displays the end date formatted to a standard European layout
                \Filament\Tables\Columns\TextColumn::make('end_date')
                    ->label('Odjezd')
                    ->date('d.m.Y')
                    ->sortable(),

                // Displays the customer's name and makes the column searchable via the top right search bar
                \Filament\Tables\Columns\TextColumn::make('customer_name')
                    ->label('Zákazník')
                    ->searchable()
                    ->sortable(),

                // Displays the customer's email address
                \Filament\Tables\Columns\TextColumn::make('customer_email')
                    ->label('E-mail')
                    ->searchable(),

                // Displays the total price paid with a currency suffix
                \Filament\Tables\Columns\TextColumn::make('paid_amount')
                    ->label('Zaplaceno')
                    ->numeric()
                    ->sortable()
                    ->suffix(' Kč'),

                // Displays the total price with a currency suffix
                \Filament\Tables\Columns\TextColumn::make('total_price')
                    ->label('Celková cena')
                    ->numeric()
                    ->sortable()
                    ->suffix(' Kč'),

                // Displays the current payment or approval status using visual color indicators
                \Filament\Tables\Columns\TextColumn::make('status')
                    ->label('Stav')
                    ->badge()
                    ->colors([
                        'warning' => 'pending',
                        'success' => 'deposit_paid',
                        'danger' => 'cancelled',
                    ]),

                // Displays a truncated preview of the customer notes, hiding the column by default
                \Filament\Tables\Columns\TextColumn::make('customer_notes')
                    ->label('Poznámky zákazníka')
                    ->limit(30)
                    ->tooltip(fn($state) => $state)
                    ->toggleable(isToggledHiddenByDefault: false),

                // Displays a truncated preview of the admin notes, hiding the column by default to save screen space
                \Filament\Tables\Columns\TextColumn::make('admin_notes')
                    ->label('Interní poznámky')
                    ->limit(30)
                    ->tooltip(fn($state) => $state)
                    ->toggleable(isToggledHiddenByDefault: false),
            ])
            ->headerActions([
                \Filament\Tables\Actions\Action::make('export_csv')
                    ->label('Exportovat CSV')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->action(fn() => static::exportCsv()),
            ])
            ->filters([
                // Allows administrators to filter the table by booking status using a dropdown
                \Filament\Tables\Filters\SelectFilter::make('status')
                    ->label('Stav')
                    ->options([
                        'pending' => 'Nezaplaceno',
                        'deposit_paid' => 'Záloha zaplacena',
                        'cancelled' => 'Zrušeno',
                    ]),
            ])
            ->actions([
                // Adds a button to the end of each row to view or edit the specific booking record
                \Filament\Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                // Allows administrators to delete multiple records simultaneously using the checkboxes
                \Filament\Tables\Actions\BulkActionGroup::make([
                    \Filament\Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    // CSV export
    protected static function exportCsv()
    {
        $fileName = 'bookings-' . now()->format('Ymd-His') . '.csv';

        $bookings = Booking::orderBy('start_date')->get();

        return response()->streamDownload(function () use ($bookings) {
            $handle = fopen('php://output', 'w');

            fputcsv($handle, [
                'ID',
                'Customer Name',
                'Customer Email',
                'Start Date',
                'End Date',
                'Status',
                'Graduate Count',
                'Student Count',
                'Child Count',
                'External Count',
                'Dog Count',
                'Total Price',
                'Deposit Amount',
                'Paid Amount',
                'Reserve Whole',
                'Created At',
            ]);

            foreach ($bookings as $booking) {
                fputcsv($handle, [
                    $booking->id,
                    $booking->customer_name,
                    $booking->customer_email,
                    $booking->start_date->format('Y-m-d'),
                    $booking->end_date->format('Y-m-d'),
                    $booking->status,
                    $booking->graduate_count,
                    $booking->student_count,
                    $booking->child_count,
                    $booking->external_count,
                    $booking->dog_count,
                    $booking->total_price,
                    $booking->deposit_amount,
                    $booking->paid_amount,
                    $booking->reserve_whole ? '1' : '0',
                    $booking->created_at->toDateTimeString(),
                ]);
            }

            fclose($handle);
        }, $fileName, [
            'Content-Type' => 'text/csv',
        ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListBookings::route('/'),
            'create' => Pages\CreateBooking::route('/create'),
            'edit' => Pages\EditBooking::route('/{record}/edit'),
        ];
    }
}