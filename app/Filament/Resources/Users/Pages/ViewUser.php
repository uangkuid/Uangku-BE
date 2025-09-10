<?php

namespace App\Filament\Resources\Users\Pages;

use App\Helpers\EncryptionHelper;
use Filament\Actions\EditAction;
use App\Filament\Resources\Users\UserResource;
use App\Filament\Resources\Users\Widgets\UserTransactionStatsWidget;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewUser extends ViewRecord
{
    protected static string $resource = UserResource::class;

    public function getTitle(): string
    {
        $record = $this->getRecord();

        return EncryptionHelper::decryptFromString($record->email, EncryptionHelper::getSystemSecretKey());
    }

    protected function getActions(): array
    {
        return [
            EditAction::make(),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            UserTransactionStatsWidget::class,
        ];
    }
}