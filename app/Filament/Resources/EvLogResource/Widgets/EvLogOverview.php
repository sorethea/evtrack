<?php

namespace App\Filament\Resources\EvLogResource\Widgets;

use App\Models\Charge;
use App\Models\DrivingLog;
use App\Models\EvLog;
use Filament\Widgets\Concerns\InteractsWithPageTable;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Number;

class EvLogOverview extends BaseWidget
{
    use InteractsWithPageTable;
    protected function getStats(): array
    {
        $minOdo = EvLog::selectRaw('MIN(odo) AS min_odo')
                ->where('date','=',now()->subMonths(12))
                ->value('min_odo');
        $maxOdo = EvLog::selectRaw('MAX(odo) AS max_odo')
            ->where('date','>=',now()->subMonths(12))
            ->value('max_odo');
        $distanceByMonth = EvLog::selectRaw('MAX(odo)-MIN(odo) AS distance,MONTH(date) AS month')
            ->where('date','>=',now()->subMonths(12))
            ->groupBy('month')
            ->pluck('distance')->toArray();
        $distance = end($distanceByMonth);
        $currency = config("ev.currency");
        return [
            Stat::make("Total driving for this month",Number::format($distance)."km")
                ->description("Odometer start from {$minOdo} to {$maxOdo}")
                ->icon('heroicon-o-map')
                ->color('success')
                ->chart($distanceByMonth),
        ];
    }
}
