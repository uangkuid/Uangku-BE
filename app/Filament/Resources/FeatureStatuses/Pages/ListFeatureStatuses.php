<?php

namespace App\Filament\Resources\FeatureStatuses\Pages;

use App\Filament\Resources\FeatureStatuses\FeatureStatusResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListFeatureStatuses extends ListRecords
{
    protected static string $resource = FeatureStatusResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
