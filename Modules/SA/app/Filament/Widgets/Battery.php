<?php

namespace Modules\SA\Filament\Widgets;

use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Log;
use Modules\SA\Models\Metric;

class Battery extends StatsOverviewWidget
{
    protected static ?string $pullingInterval ='10s';
    protected function getStats(): array
    {
        $latest = Metric::latest('recorded_at')->first();
        $batterySoc = $latest->metadata['total/battery_state_of_charge'] ?? '--';
        $pvPower    = $latest['total/pv_power'] ?? '--';
        $loadPower  = $latest['total/load_power'] ?? '--';
        $gridPower  = $latest['total/grid_power'] ?? '--';
        $battery1Soc = $latest['battery_1/state_of_charge'] ?? '--';
        $battery2Soc = $latest['battery_2/state_of_charge'] ?? '--';
        $temperature = $latest['weather/outside_temperature'] ?? '--';

        return [
            Stat::make('Battery SoC', json_encode($latest->metadata["total/battery_state_of_charge"]) . '%')
                ->description('Real‑time battery level')
                ->descriptionIcon('heroicon-m-battery-100')
                ->color('success'),
        ];
    }
}
