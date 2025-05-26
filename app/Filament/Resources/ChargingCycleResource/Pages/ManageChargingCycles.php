<?php

namespace App\Filament\Resources\ChargingCycleResource\Pages;

use App\Filament\Resources\ChargingCycleResource;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;

class ManageChargingCycles extends ManageRecords
{
    protected static string $resource = ChargingCycleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()->hidden(),
        ];
    }
}
