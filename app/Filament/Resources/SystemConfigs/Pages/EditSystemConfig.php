<?php

namespace App\Filament\Resources\SystemConfigs\Pages;

use Filament\Actions\DeleteAction;
use App\Filament\Resources\SystemConfigs\SystemConfigResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Auth;

class EditSystemConfig extends EditRecord
{
    protected static string $resource = SystemConfigResource::class;

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

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $data['updated_by'] = Auth::id();
        return $data;
    }
}
