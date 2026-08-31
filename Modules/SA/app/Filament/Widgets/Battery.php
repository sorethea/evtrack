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
        $batterySoc = $metadata['total/battery_state_of_charge'] ?? '--';
        $pvPower    = $metadata['total/pv_power'] ?? '--';
        $loadPower  = $metadata['total/load_power'] ?? '--';
        $gridPower  = $metadata['total/grid_power'] ?? '--';
        $battery1Soc = $metadata['battery_1/state_of_charge'] ?? '--';
        $battery2Soc = $metadata['battery_2/state_of_charge'] ?? '--';
        $temperature = $metadata['weather/outside_temperature'] ?? '--';

        return [
            Stat::make('Battery SoC', $batterySoc . '%')
                ->description('Real‑time battery level')
                ->descriptionIcon('heroicon-m-battery-100')
                ->color('success'),
        ];
    }
}
