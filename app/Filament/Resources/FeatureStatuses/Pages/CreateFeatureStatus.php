<?php

namespace App\Filament\Resources\FeatureStatuses\Pages;

use App\Filament\Resources\FeatureStatuses\FeatureStatusResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateFeatureStatus extends CreateRecord
{
    protected static string $resource = FeatureStatusResource::class;

    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('index');
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['updated_by'] = auth()->id();

        return $data;
    }
}
