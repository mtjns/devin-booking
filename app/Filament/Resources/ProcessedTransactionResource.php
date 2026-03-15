<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProcessedTransactionResource\Pages;
use App\Models\ProcessedTransaction;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class ProcessedTransactionResource extends Resource
{
    protected static ?string $model = ProcessedTransaction::class;

    protected static ?string $navigationIcon = 'heroicon-o-banknotes';

    protected static ?string $navigationLabel = 'Zpracované Platby';

    protected static ?string $modelLabel = 'Zpracovaná Platba';

    protected static ?string $pluralModelLabel = 'Zpracované Platby';

    protected static ?int $navigationSort = 40;

    public static function canViewAny(): bool
    {
        return auth()->user()?->can_manage_financials ?? false;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('ID')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('transaction_id')
                    ->label('ID Transakce')
                    ->sortable()
                    ->searchable()
                    ->copyable()
                    ->tooltip('ID z banky (Fio Bank)')
                    ->size('sm'),

                Tables\Columns\TextColumn::make('variable_symbol')
                    ->label('VS')
                    ->sortable()
                    ->searchable()
                    ->size('sm'),

                Tables\Columns\TextColumn::make('booking.id')
                    ->label('Rezervace')
                    ->url(
                        fn(ProcessedTransaction $record): string => $record->booking_id
                        ? route('filament.admin.resources.bookings.edit', $record->booking)
                        : '#'
                    )
                    ->color('warning')
                    ->formatStateUsing(fn($state) => $state ? "#{$state}" : 'N/A')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('booking.customer_name')
                    ->label('Zákazník')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('amount')
                    ->label('Částka')
                    ->sortable()
                    ->numeric(decimalPlaces: 0)
                    ->formatStateUsing(fn($state) => $state . ' Kč')
                    ->alignment('right'),

                Tables\Columns\BadgeColumn::make('status')
                    ->label('Status')
                    ->sortable()
                    ->colors([
                        'success' => 'processed',
                        'warning' => 'skipped',
                    ])
                    ->formatStateUsing(fn(string $state): string => match ($state) {
                        'processed' => '✅ Zpracováno',
                        'skipped' => '⏭️ Přeskočeno',
                        default => $state,
                    }),

                Tables\Columns\TextColumn::make('notes')
                    ->label('Poznámky')
                    ->limit(50)
                    ->tooltip(fn(ProcessedTransaction $record) => $record->notes)
                    ->wrap(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Čas Zpracování')
                    ->dateTime('d.m.Y H:i:s')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        'processed' => '✅ Zpracováno',
                        'skipped' => '⏭️ Přeskočeno',
                    ]),

                Tables\Filters\SelectFilter::make('booking_id')
                    ->label('Rezervace')
                    ->relationship('booking', 'id')
                    ->searchable()
                    ->preload(),

                Tables\Filters\Filter::make('created_at')
                    ->form([
                        Forms\Components\DatePicker::make('created_from')
                            ->label('Od'),
                        Forms\Components\DatePicker::make('created_until')
                            ->label('Do'),
                    ])
                    ->query(function ($query, array $data) {
                        return $query
                            ->when(
                                $data['created_from'] ?? null,
                                fn($query, $date) => $query->whereDate('created_at', '>=', $date),
                            )
                            ->when(
                                $data['created_until'] ?? null,
                                fn($query, $date) => $query->whereDate('created_at', '<=', $date),
                            );
                    }),
            ])
            ->defaultSort('created_at', 'desc')
            ->paginated([25, 50, 100])
            ->striped();
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListProcessedTransactions::route('/'),
        ];
    }
}
