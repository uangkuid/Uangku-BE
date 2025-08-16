<?php

namespace App\Filament\Resources\Categories\Pages;

use Filament\Actions\EditAction;
use App\Filament\Resources\Categories\CategoryResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewCategory extends ViewRecord
{
    protected static string $resource = CategoryResource::class;

    public function getTitle(): string
    {
        $record = $this->getRecord();

        return $record->name;
    }

    protected function getActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
