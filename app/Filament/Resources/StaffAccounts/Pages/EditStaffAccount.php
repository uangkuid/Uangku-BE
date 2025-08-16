<?php

namespace App\Filament\Resources\StaffAccounts\Pages;

use Filament\Actions\DeleteAction;
use App\Filament\Resources\StaffAccounts\StaffAccountResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditStaffAccount extends EditRecord
{
    protected static string $resource = StaffAccountResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('index');
    }
}
