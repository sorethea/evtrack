<?php

namespace Modules\EV\Filament\Resources\ChargingCycleResource\Pages;

use Filament\Resources\Pages\ViewRecord;
use Modules\EV\Filament\Resources\ChargingCycleResource;

class ViewChargingCycle extends ViewRecord
{
    protected static string $resource = ChargingCycleResource::class;

    protected static ?string $title = "Dashboard";

    protected function getHeaderWidgets(): array
    {
        return [
            ChargingCycleResource\Widgets\ChargingCycleOverview::make([
                'record'=>$this->record,
            ]),
            ChargingCycleResource\Widgets\VoltageChart::make([
                'record'=>$this->record,
            ]),
            ChargingCycleResource\Widgets\EnergyChart::make([
                'record'=>$this->record,
            ]),
            ChargingCycleResource\Widgets\TemperatureChart::make([
                'record'=>$this->record,
            ]),
            ChargingCycleResource\Widgets\CapacityChart::make([
                'record'=>$this->record,
            ]),
            ChargingCycleResource\Widgets\ConsumptionChart::make([
                'record'=>$this->record,
            ]),
            ChargingCycleResource\Widgets\EfficiencyChart::make([
                'record'=>$this->record,
            ]),
            ChargingCycleResource\Widgets\BatteryVoltageChart::make([
                'record'=>$this->record,
            ]),
//            ChargingCycleResource\Widgets\MiddleSoCChart::make([
//                'record'=>$this->record,
//            ]),
        ];
    }
}
