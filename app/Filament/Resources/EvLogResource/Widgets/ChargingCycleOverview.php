<?php

namespace App\Filament\Resources\EvLogResource\Widgets;

use App\Models\EvLog;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class ChargingCycleOverview extends BaseWidget
{

    protected function getStats(): array
    {
        $lastChargingCycle = EvLog::where('log_type','charging')->orderBy('date','desc')->first();
        return [
            Stat::make('Last charging date',$lastChargingCycle->date)
        ];
    }
}
