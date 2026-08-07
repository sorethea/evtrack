<?php

namespace App\Filament\Resources\DrivingLogResource\Pages;

use App\Filament\Resources\DrivingLogResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditDrivingLog extends EditRecord
{
    protected static string $resource = DrivingLogResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
