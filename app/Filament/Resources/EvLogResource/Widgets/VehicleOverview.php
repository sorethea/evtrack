<?php

namespace App\Filament\Resources\EvLogResource\Widgets;

use App\Helpers\EvLog;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Number;
use Symfony\Component\Console\Helper\Helper;

class VehicleOverview extends BaseWidget
{
    protected function getStats(): array
    {
        $vehicle = auth()->user()->vehicle;
        $log = $vehicle->latestLog;
        $odo = EvLog::getItemValue($log,1);
        $soc = EvLog::getItemValue($log,11);
        $ac = EvLog::getItemValue($log,19);
        $ad = EvLog::getItemValue($log,20);
        $ad = evLog::getItemValue($log,20);
        return [
            Stat::make(trans('ev.odo'),Number::format($odo).'km'),
            Stat::make(trans('ev.soc'),Number::format($soc).'%'),
            Stat::make(trans('ev.accumulative').' '.trans('ev.charge'),Number::format($ac).'kWh'),
            Stat::make(trans('ev.accumulative').' '.trans('ev.discharge'),Number::format($ad).'kWh'),
        ];
    }
}
