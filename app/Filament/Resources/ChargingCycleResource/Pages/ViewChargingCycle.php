<?php

namespace App\Filament\Resources\ChargingCycleResource\Pages;

use App\Filament\Resources\ChargingCycleResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewChargingCycle extends ViewRecord
{
    protected static string $resource = ChargingCycleResource::class;

    protected function getHeaderWidgets(): array
    {
        return [
            ChargingCycleResource\Widgets\ChargingCycleOverview::make([
                'record_id'=>$this->record->id,
            ]),
        ];
    }
}
