<?php

namespace App\Filament\Resources\StaffAccountResource\Pages;

use App\Filament\Resources\StaffAccountResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListStaffAccounts extends ListRecords
{
    protected static string $resource = StaffAccountResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
