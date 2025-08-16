<?php

namespace App\Filament\Resources\StaffAccounts\Pages;

use App\Filament\Resources\StaffAccounts\StaffAccountResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateStaffAccount extends CreateRecord
{
    protected static string $resource = StaffAccountResource::class;

    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('index');
    }
}
