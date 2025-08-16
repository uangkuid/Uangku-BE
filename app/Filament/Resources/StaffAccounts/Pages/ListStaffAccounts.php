<?php

namespace App\Filament\Resources\StaffAccounts\Pages;

use Filament\Actions\CreateAction;
use App\Filament\Resources\StaffAccounts\StaffAccountResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListStaffAccounts extends ListRecords
{
    protected static string $resource = StaffAccountResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
