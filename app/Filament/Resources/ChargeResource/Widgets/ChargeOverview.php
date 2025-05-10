<?php

namespace App\Filament\Resources\ChargeResource\Widgets;

use App\Models\Charge;
use App\Models\DrivingLog;
use App\Models\Trip;
use Filament\Widgets\Concerns\InteractsWithPageTable;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Number;
use Spatie\Color\Distance;

class ChargeOverview extends BaseWidget
{
    use InteractsWithPageTable;
    protected function getStats(): array
    {
        $minOdo = DrivingLog::selectRaw('MIN(odo) AS min_odo')
            ->where('date','>=',now()->subMonths(12))
            ->value('min_odo');
        $maxOdo = DrivingLog::selectRaw('MAX(odo) AS max_odo')
            ->where('date','>=',now()->subMonths(12))
            ->value('max_odo');
        $distance = DrivingLog::selectRaw('MAX(odo)-MIN(odo) AS distance')
            ->where('date','>=',now()->subMonths(12))
            ->value('distance');
        $distanceByMonth = DrivingLog::selectRaw('MAX(odo)-MIN(odo) AS distance,MONTH(date) AS month')
            ->where('date','>=',now()->subMonths(12))
            ->groupBy('month')
            ->pluck('distance');
        $total = Charge::selectRaw('SUM(`qty`*`price`) as `cost`')
            ->where('date','>=',now()->subMonths(12))
            ->value('cost');
        $totalByMonth = Charge::selectRaw('SUM(`qty`*`price`) as `cost`, MONTH(date) as month')
            ->where('date','>=',now()->subMonths(12))
            ->groupBy('month')
            ->pluck('cost');
        $totalEnergy = Charge::selectRaw('SUM(`qty`) as `energy`')
            ->where('date','>=',now()->subMonths(12))
            ->value('energy');
        $totalEnergyByMonth = Charge::selectRaw('SUM(`qty`) as `energy`, MONTH(date) as month')
            ->where('date','>=',now()->subMonths(12))
            ->groupBy('month')
            ->pluck('energy');
        $currency = config("ev.currency");
        $rate = config("ev.usd_rate");
        $total_cost = round($total/$rate,2);
        $energyPer100km = Number::format(100*$totalEnergy/$distance,1).'km';
        $cost100km = Number::currency(100*$total_cost/$distance,$currency);
        $total_cost = Number::currency($total_cost,$currency);
        return [
            Stat::make("Total Charging Cost", $total_cost)
                ->description("Average charging cost per 100km: {$cost100km}")
                ->icon('heroicon-o-currency-dollar')
                ->color('danger')
                ->chart($totalByMonth->toArray()),
            Stat::make("Total Charging Energy",Number::format(round($totalEnergy,0)).'kWh')
                ->description("Average consumption per 100km: {$energyPer100km}")
                ->icon('heroicon-o-bolt')
                ->color('warning')
                ->chart($totalEnergyByMonth->toArray()),
            Stat::make("Total Driving Distance",Number::format($distance)."km")
                ->description("Odometer start from {$minOdo} to {$maxOdo}")
                ->icon('heroicon-o-map')
                ->color('success')
                ->chart($distanceByMonth->toArray()),
        ];
    }
}
