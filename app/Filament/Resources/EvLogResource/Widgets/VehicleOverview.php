<?php

namespace App\Filament\Resources\EvLogResource\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class VehicleOverview extends BaseWidget
{
    protected function getStats(): array
    {
        $vehicle = auth()->user()->vehicle;
        $log = $vehicle->latestLog;

        return [
            Stat::make(trans('ev.odo'),\Illuminate\Support\Number::format($log->odo).'km'),
        ];
    }
}
