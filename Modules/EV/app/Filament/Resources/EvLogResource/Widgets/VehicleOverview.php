<?php

namespace Modules\EV\Filament\Resources\EvLogResource\Widgets;

use Filament\Support\Colors\Color;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Number;

class VehicleOverview extends BaseWidget
{
    protected function getStats(): array
    {
        $vehicle = auth()->user()->vehicle;
        $log = $vehicle->latestLog;
        $distance = $log->cycleView->distance;
        $cycleDistanceArray = $log->cycleView->logs->pluck('distance')->toArray();
        $soc = $log->detail->soc;
        $cycleSoCArray = $log->cycleView->logs->pluck('soc')->toArray();
        $ac =  $log->detail->ac;
        $ad =  $log->detail->ad;

        return [
            Stat::make(trans('ev.distance'),Number::format($distance).'km')
                //->icon('custom-location-color-bookmark-add')
                ->color(Color::Green)
                ->chart($cycleDistanceArray),
            Stat::make(trans('ev.soc'),Number::format($soc).'%')
                //->icon('custom-percentage')
                ->color(Color::Red)
                ->chart($cycleSoCArray),
            Stat::make(trans('ev.accumulative').' '.trans('ev.charge'),Number::format($ac).'kWh'),
            Stat::make(trans('ev.accumulative').' '.trans('ev.discharge'),Number::format($ad).'kWh'),
        ];
    }

    protected function getColumns(): int
    {
        return 4;
    }
}
