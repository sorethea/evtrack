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
        $distanceByMonth = EvLog::selectRaw('SUM(ev_logs.odo - COALESCE(parent.odo, 0)) AS distance,
        DATE_FORMAT(ev_logs.date,"%Y-%m") AS month')
            ->leftJoin('ev_logs as parent', 'ev_logs.parent_id', 'parent.id')
            ->where('ev_logs.date','>=',now()->subMonths(12))
            ->groupBy('month')
            ->orderBy('month')
            ->get();
        $chargeByMonth = EvLog::selectRaw('COUNT(ev_logs.ac - COALESCE(parent.ac, 0)) AS charge_count,
        SUM(ev_logs.ac - COALESCE(parent.ac, 0)) AS charge,
        DATE_FORMAT(ev_logs.date,"%Y-%m") AS month')
            ->leftJoin('ev_logs as parent', 'ev_logs.parent_id', 'parent.id')
            ->where('ev_logs.log_type','=','charging')
            ->where('ev_logs.date','>=',now()->subMonths(12))
            ->groupBy('month')
            ->orderBy('month')
            ->get();
        $dischargeByMonth = EvLog::selectRaw('
                SUM(ev_logs.ac-COALESCE(parent.ac, 0)) AS regen,
                SUM(ev_logs.ad - COALESCE(parent.ad, 0)-(ev_logs.ac-COALESCE(parent.ac, 0))) AS discharge,
                DATE_FORMAT(ev_logs.date,"%Y-%m") AS month')
            ->leftJoin('ev_logs as parent', 'ev_logs.parent_id', 'parent.id')
            ->where('ev_logs.date','>=',now()->subMonths(12))
            ->where('ev_logs.log_type','=','driving')
            ->groupBy('month')
            ->orderBy('month')
            ->get();
        $distanceByMonthArray=$distanceByMonth->pluck("distance","month")->toArray();
        $distance = end($distanceByMonthArray);
        $chargeByMonthArray = $chargeByMonth->pluck("charge","month")->toArray();
        $charge = end($chargeByMonthArray);
        $dischargeByMonthArray = $dischargeByMonth->pluck('discharge','month')->toArray();
        $regenArray = $dischargeByMonth->pluck('regen','month')->toArray();
        $regen = end($regenArray);
        $discharge = end($dischargeByMonthArray);
        $grossDischarge = $discharge + $regen;
        $chargeCount = array_key_last($chargeByMonthArray);
        $thisMonth = now()->format('M, Y');
        $currency = config("ev.currency");
        return [
            Stat::make("Total driving in {$thisMonth}",Number::format($distance)."km")
                ->description("Odometer start from {$minOdo} to {$maxOdo}")
                ->icon('heroicon-o-map')
                ->color('success')
                ->chart($distanceByMonthArray),
            Stat::make("Total charging in {$thisMonth}",Number::format($charge)."kWh")
                ->description("Charged {$chargeCount} time(s)")
                ->icon('heroicon-o-bolt')
                ->color('danger')
                ->chart($chargeByMonthArray),
            Stat::make("Total discharge in {$thisMonth}",Number::format($discharge)."kWh")
                ->description("Gross discharge {$grossDischarge}kWh, and braking {$regen}kWh")
                ->icon('heroicon-o-bolt-slash')
                ->color('warning')
                ->chart($dischargeByMonthArray),
        ];
    }
}
