<?php

namespace App\Filament\Resources\BookingResource\Pages;

use App\Filament\Resources\BookingResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Filament\Notifications\Notification;
use Filament\Support\Exceptions\Halt;

class EditBooking extends EditRecord
{
    protected static string $resource = BookingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    // Intercepts the save action to prevent race conditions (Optimistic Locking)
    protected function beforeSave(): void
    {
        // Fetches the absolute latest timestamp directly from the database
        $latestDatabaseTimestamp = \App\Models\Booking::where('id', $this->record->id)->value('updated_at');

        // Compares the database timestamp against the timestamp loaded in the browser tab
        if ($latestDatabaseTimestamp && $latestDatabaseTimestamp->gt($this->record->updated_at)) {

            // Reload the latest data from database
            $this->record = $this->record->fresh();

            // Refresh form fields with new data
            $this->form->fill($this->record->toArray());

            Notification::make()
                ->warning()
                ->title('Pozor: Záznam byl mezitím změněn!')
                ->body('Záznam byl změněn jiným uživatelem. Formulář byl aktualizován nejnovějšími daty.')
                ->persistent()
                ->send();

            // Completely halts the save process
            throw new Halt();
        }
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}