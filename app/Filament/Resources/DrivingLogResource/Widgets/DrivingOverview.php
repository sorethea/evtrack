<?php

namespace App\Filament\Resources\DrivingLogResource\Widgets;

use App\Models\DrivingLog;
use Filament\Widgets\Concerns\InteractsWithPageTable;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Number;

class DrivingOverview extends BaseWidget
{
    use InteractsWithPageTable;
    protected function getStats(): array
    {
        $vehicle = auth()->user()->vehicle;
        $weeklyConsumptions = DrivingLog::selectRaw("(soc_to-soc_from)*{$vehicle->capacity}/100 AS consumption")
            ->where("date",">=",now()->subWeek())
            ->pluck("consumption");
        $totalWeeklyConsumption = array_sum($weeklyConsumptions->toArray());
        $averageWeeklyConsumption = $totalWeeklyConsumption/count($weeklyConsumptions->toArray());
        return [
            Stat::make("Total Weekly Consumption", $totalWeeklyConsumption)
                ->description("Average weekly consumption: {$averageWeeklyConsumption}")
                ->icon('heroicon-o-bolt')
                ->color('danger')
                ->chart($weeklyConsumptions->toArray()),
        ];

    }
}
