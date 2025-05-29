<?php

namespace App\Filament\Resources\FeatureStatusResource\Pages;

use App\Filament\Resources\FeatureStatusResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListFeatureStatuses extends ListRecords
{
    protected static string $resource = FeatureStatusResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
