<?php

namespace Modules\EV\Filament\Resources\EvLogResource\Widgets;

use App\Models\EvLog;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;

class ChargingCycleOverview extends BaseWidget
{

    protected function getStats(): array
    {
        $lastChargingCycle = EvLog::where('log_type','charging')->orderBy('date','desc')->first();
        $distance = 0;
        $discharge = 0;
        $regen = 0;
        foreach ($lastChargingCycle->children as $child){
            $distance +=$child->daily->distance;
            $discharge +=($child->daily->a_discharge-$child->daily->a_charge);
            $regen +=(-$child->daily->a_charge);
        }
        $lastChild = $lastChargingCycle->children()->latest('date')->first();
        return [
//            Stat::make('Total Distance', Number::format($distance,1).'km'),
//            Stat::make('Total Charge',Number::format($lastChargingCycle->daily->energy,1).'kWh'),
//            Stat::make('Total Regenerative Braking',Number::format($regen,1).'kWh'),
//            Stat::make('Total Discharge', Number::format($discharge,1).'kWh'),
//            Stat::make('kWh/100km', $distance>0?Number::format(100 * $discharge/$distance,1):0),
//            Stat::make('Gap Zero', Number::format($lastChild->daily->gap_zero,1).'kWh'),
        ];
    }
}
