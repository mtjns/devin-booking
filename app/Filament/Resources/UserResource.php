<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserResource\Pages;
use App\Filament\Resources\UserResource\RelationManagers;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'heroicon-o-user';

    protected static ?string $navigationGroup = 'Správa';

    // Sets the plural and singular names for the resource in the navigation menu
    protected static ?string $modelLabel = 'Administrátor';
    protected static ?string $pluralModelLabel = 'Administrátoři';

    protected static ?string $navigationLabel = 'Správa administrátorů';


    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                // Standard text input for the user's full name
                \Filament\Forms\Components\TextInput::make('name')
                    ->required()
                    ->maxLength(255),

                // Validates that the input is a properly formatted email address
                \Filament\Forms\Components\TextInput::make('email')
                    ->email()
                    ->required()
                    ->maxLength(255),

                // Handles password input securely, masking the characters
                // Requires a password only when creating a new user, allows leaving it blank to keep the current password when editing
                \Filament\Forms\Components\TextInput::make('password')
                    ->password()
                    ->required(fn(string $context): bool => $context === 'create')
                    ->dehydrated(fn($state) => filled($state))
                    ->maxLength(255),

                // Groups all the permission toggles into a distinct visual block on the screen
                \Filament\Forms\Components\Section::make('Systémová oprávnění')
                    ->description('Toggle the specific actions this staff member is allowed to perform.')
                    ->schema([
                        // The master override switch
                        \Filament\Forms\Components\Toggle::make('is_super_admin')
                            ->label('Super administrátor')
                            ->helperText('Grants absolute access to all system features.')
                            ->columnSpanFull(),

                        // Controls access to the user management area
                        \Filament\Forms\Components\Toggle::make('can_manage_users')
                            ->label('Může spravovat uživatele'),

                        // Controls read-only access to the booking calendar and lists
                        \Filament\Forms\Components\Toggle::make('can_view_bookings')
                            ->label('Může zobrazit rezervace'),

                        // Controls the ability to create, modify, or cancel reservations
                        \Filament\Forms\Components\Toggle::make('can_edit_bookings')
                            ->label('Může upravovat rezervace'),

                        // Controls access to pricing rules, revenue data, and bank integrations
                        \Filament\Forms\Components\Toggle::make('can_manage_financials')
                            ->label('Může spravovat finance'),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                \Filament\Tables\Columns\TextColumn::make('name')
                    ->sortable(),
                \Filament\Tables\Columns\TextColumn::make('email')
                    ->sortable()
                    ->searchable(),
                \Filament\Tables\Columns\IconColumn::make('is_super_admin')
                    ->boolean()
                    ->label('Super administrátor'),
                \Filament\Tables\Columns\IconColumn::make('can_manage_users')
                    ->boolean()
                    ->label('Správa uživatelů'),
                \Filament\Tables\Columns\IconColumn::make('can_view_bookings')
                    ->boolean()
                    ->label('Zobrazení rezervací'),
                \Filament\Tables\Columns\IconColumn::make('can_edit_bookings')
                    ->boolean()
                    ->label('Úprava rezervací'),
                \Filament\Tables\Columns\IconColumn::make('can_manage_financials')
                    ->boolean()
                    ->label('Správa financí'),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->visible(function (User $record): bool {
                        $user = auth()->user();

                        if (!$user) {
                            return false;
                        }

                        if ($record->is_super_admin && !$user->is_super_admin) {
                            return false;
                        }

                        return true;
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
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
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
        ];
    }
}
