<?php

namespace App\Filament\Resources\EvLogResource\Widgets;

use App\Models\Charge;
use App\Models\DrivingLog;
use App\Models\EvLog;
use Carbon\Carbon;
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
                ->whereMonth('date',now()->month)
                ->whereYear('date',now()->year)
                ->value('min_odo');
        $maxOdo = EvLog::selectRaw('MAX(odo) AS max_odo')
            ->whereMonth('date',now()->month)
            ->whereYear('date',now()->year)
            ->value('max_odo');
        $distanceByMonth = EvLog::selectRaw('ev_logs.odo - COALESCE(parent.odo, 0) AS distance,MONTH(ev_logs.date) AS month')
            ->leftJoin('ev_logs as parent', 'ev_logs.parent_id', 'parent.id')
            ->where('ev_logs.date','>=',now()->subMonths(12))
            //->groupBy('month')
            ->pluck('distance','month')->toArray();
        $chargeByMonth = EvLog::selectRaw('COUNT(ev_logs.ac - COALESCE(parent.ac, 0)) AS charge_count,SUM(ev_logs.ac - COALESCE(parent.ac, 0)) AS charge,MONTH(ev_logs.date) AS month')
            ->leftJoin('ev_logs as parent', 'ev_logs.parent_id', 'parent.id')
            ->where('ev_logs.log_type','=','charging')
            ->where('ev_logs.date','>=',now()->subMonths(12))
            ->groupBy('month')
            ->pluck('charge','charge_count')->toArray();
        $dischargeByMonth = EvLog::selectRaw('
                SUM(ev_logs.ad - COALESCE(parent.ad, 0)) AS discharge,
                MONTH(ev_logs.date) AS month')
            ->leftJoin('ev_logs as parent', 'ev_logs.parent_id', 'parent.id')
            ->where('ev_logs.date','>=',now()->subMonths(12))
            ->groupBy('month')
            ->pluck('discharge')->toArray();
        logger($distanceByMonth);
        $distance = end($distanceByMonth);
        $charge = end($chargeByMonth);
        $discharge = end($dischargeByMonth);
        $chargeCount = array_key_last($chargeByMonth);
        $thisMonth = now()->format('M, Y');
        $currency = config("ev.currency");
        return [
            Stat::make("Total driving in {$thisMonth}",Number::format($distance)."km")
                ->description("Odometer start from {$minOdo} to {$maxOdo}")
                ->icon('heroicon-o-map')
                ->color('success')
                ->chart($distanceByMonth),
            Stat::make("Total charging in {$thisMonth}",Number::format($charge)."kWh")
                ->description("Charged {$chargeCount} time(s)")
                ->icon('heroicon-o-bolt')
                ->color('danger')
                ->chart($chargeByMonth),
//            Stat::make("Total discharge in {$thisMonth}",Number::format($discharge)."kWh")
//                //->description("Charged {$chargeCount} time(s)")
//                ->icon('heroicon-o-bolt-slash')
//                ->color('danger')
//                ->chart($chargeByMonth),
        ];
    }
}
