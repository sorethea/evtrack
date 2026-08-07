<?php

namespace App\Filament\Resources\Obd2LogsResource\Pages;

use App\Filament\Resources\Obd2LogsResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditObd2Logs extends EditRecord
{
    protected static string $resource = Obd2LogsResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
