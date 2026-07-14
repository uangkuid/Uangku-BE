<?php

namespace App\Filament\Resources\Users\Pages;

use App\Filament\Resources\Users\UserResource;
use App\Filament\Resources\Users\Widgets\UserTransactionStatsWidget;
use App\Helpers\EncryptionHelper;
use Filament\Resources\Pages\ViewRecord;

class ViewUser extends ViewRecord
{
    protected static string $resource = UserResource::class;

    public function getTitle(): string
    {
        $record = $this->getRecord();

        return EncryptionHelper::decryptEmail($record->email);
    }

    protected function getHeaderWidgets(): array
    {
        return [
            UserTransactionStatsWidget::class,
        ];
    }
}
