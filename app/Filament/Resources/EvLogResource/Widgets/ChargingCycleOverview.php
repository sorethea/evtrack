<?php

namespace App\Filament\Resources\EvLogResource\Widgets;

use App\Models\EvLog;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Number;

class ChargingCycleOverview extends BaseWidget
{

    protected function getStats(): array
    {
        $lastChargingCycle = EvLog::where('log_type','charging')->orderBy('date','desc')->first();
        return [
            Stat::make('Total Charge',Number::format($lastChargingCycle->daily->energy,1).'kWh')
        ];
    }
}
