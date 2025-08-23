<?php

namespace Modules\EV\Filament\Resources\EvLogResource\Widgets;

use Filament\Support\Colors\Color;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Number;
use Modules\EV\Helpers\EvLog;

class VehicleOverview extends BaseWidget
{
    protected function getStats(): array
    {
        $vehicle = auth()->user()->vehicle;
        $log = $vehicle->latestLog;
        $distance = $log->cycleView->distance;
        $cycleDistanceArray = $log->cycleView->logs->pluck('distance')->toArray();
        $soc = $log->detail->soc;
        $remainRange = (100*$distance/(100-$soc))-$distance;
        $cycleSoCArray = $log->cycleView->logs->pluck('soc')->toArray();
        $voltage =  $log->detail->voltage;
        $cycleVoltageArray = $log->cycleView->logs->pluck('voltage')->toArray();
        $avgVoltage = $voltage/200;
        $voltageBasedSoC = EvLog::socVoltageBased($avgVoltage);
        $ac =  $log->detail->ac;
        $ad =  $log->detail->ad;

        $netDischarge = $log->cycleView->discharge - $log->cycleView->charge;
        $regenPercentage = 100*$log->cycleView->charge/$log->cycleView->discharge ;
        $cycleDischargeArray = $log->cycleView->logs->pluck('discharge')->toArray();
        return [
            Stat::make(trans('ev.distance'),Number::format($distance).'km')
                //->icon('custom-location-color-bookmark-add')
                ->color(Color::Green)
                ->description('Remaining range: '.Number::format($remainRange,1).' km')
                ->chart($cycleDistanceArray),
            Stat::make(trans('ev.soc'),Number::format($soc).'%')
                ->description('Cell voltage based SoC: '.Number::format($voltageBasedSoC,1).'%')
                ->color(Color::Red)
                ->chart($cycleSoCArray),
            Stat::make(trans('ev.battery_voltage'),Number::format($voltage).'V')
                ->color(Color::Yellow)
                ->description('Average cell voltage: '.Number::format($avgVoltage,3).'V')
                ->chart($cycleVoltageArray),
            Stat::make(trans('ev.net_discharge'),Number::format($netDischarge).'kWh')
                ->description('Regen vs. Gross Discharge: '.Number::format($regenPercentage,1).'%')
                ->chart($cycleDischargeArray)
                ->color(Color::Teal),
            //Stat::make(trans('ev.accumulative').' '.trans('ev.discharge'),Number::format($ad).'kWh'),
        ];
    }

    protected function getColumns(): int
    {
        return 4;
    }
}
