<?php

namespace Modules\EV\Filament\Resources\EvLogResource\Widgets;


use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Modules\EV\Helpers\EvLog;

class VehicleOverview extends BaseWidget
{
    protected function getStats(): array
    {
        $vehicle = auth()->user()->vehicle;
        $log = $vehicle->latestLog;

        return EvLog::getCycleOverview($log);
    }

    protected function getColumns(): int
    {
        return 4;
    }
}
