<?php

namespace App\Filament\Resources\ChargingCycleResource\Pages;

use App\Filament\Resources\ChargingCycleResource;
use App\Filament\Resources\EvLogResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewChargeCycle extends ViewRecord
{
    protected static string $resource = ChargingCycleResource::class;

    protected function getHeaderWidgets(): array
    {
        return  [
            ChargingCycleResource\Widgets\ChargingCycleOverview::class,
        ];
    }
    protected function getHeaderActions(): array
    {
        return [
            //Actions\EditAction::make(),
        ];
    }
}
