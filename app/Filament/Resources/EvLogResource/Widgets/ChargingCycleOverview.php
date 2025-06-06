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
        $distance = 0;
        $discharge = 0;
        foreach ($lastChargingCycle->children as $child){
            $distance +=$child->daily->distance;
            $discharge +=$child->daily->discharge;
        }
        return [
            Stat::make('Total Charge',Number::format($lastChargingCycle->daily->energy,1).'kWh'),
            Stat::make('Total distance', Number::format($distance,1).'km'),
            Stat::make('Total discharge', Number::format($discharge,1).'kWh')
        ];
    }
}
