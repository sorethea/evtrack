<?php

namespace App\Filament\Resources\EvLogResource\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Number;

class VehicleOverview extends BaseWidget
{
    protected function getStats(): array
    {
        $vehicle = auth()->user()->vehicle;
        $log = $vehicle->latestLog;
        $odo = \evlog::getItemValue($log,1);
        $soc = \evlog::getItemValue($log,11);
        $ac = \evlog::getItemValue($log,19);
        $ad = \evlog::getItemValue($log,20);
        return [
            Stat::make(trans('ev.odo'),Number::format($odo).'km'),
            Stat::make(trans('ev.soc'),Number::format($soc).'%'),
            Stat::make(trans('ev.accumulative').' '.trans('ev.charge'),Number::format($ac).'kWh'),
            Stat::make(trans('ev.accumulative').' '.trans('ev.discharge'),Number::format($ad).'kWh'),
        ];
    }
}
