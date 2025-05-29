<?php

namespace App\Filament\Resources\StaffAccountResource\Pages;

use App\Filament\Resources\StaffAccountResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditStaffAccount extends EditRecord
{
    protected static string $resource = StaffAccountResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('index');
    }
}
