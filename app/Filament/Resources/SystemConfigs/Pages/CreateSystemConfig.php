<?php

namespace App\Filament\Resources\SystemConfigs\Pages;

use App\Filament\Resources\SystemConfigs\SystemConfigResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Log;

class CreateSystemConfig extends CreateRecord
{
    protected static string $resource = SystemConfigResource::class;

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
