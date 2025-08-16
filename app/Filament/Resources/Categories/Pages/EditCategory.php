<?php

namespace App\Filament\Resources\Categories\Pages;

use Filament\Actions\DeleteAction;
use App\Filament\Resources\Categories\CategoryResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Log;

class EditCategory extends EditRecord
{
    protected static string $resource = CategoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        if (!empty($data['icon'])) {
            $data['icon'] = preg_replace('/^category\//', '', $data['icon']);
        }
        return $data;
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {

        if (isset($data['icon']) && is_string($data['icon'])) {
            // Add the 'category/' prefix to the icon path
            $data['icon'] = "category/{$data['icon']}";
        }

        return parent::mutateFormDataBeforeFill($data);
    }

    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('index');
    }
}
