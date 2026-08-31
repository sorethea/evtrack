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
        $pvPower    = $latest->metadata['total/pv_power'] ?? '--';
        $loadPower  = $latest->metadata['total/load_power'] ?? '--';
        $gridPower  = $latest->metadata['total/grid_power'] ?? '--';
        $battery1Soc = $latest->metadata['battery_1/state_of_charge'] ?? '--';
        $battery2Soc = $latest->metadata['battery_2/state_of_charge'] ?? '--';
        $temperature = $latest->metadata['weather/outside_temperature'] ?? '--';

        return [
            Stat::make('Battery SoC', $batterySoc. '%')
                ->description('Real‑time battery level')
                ->descriptionIcon('heroicon-m-battery-100')
                ->color('success'),
            Stat::make('PV Power', $pvPower. 'kWh')
                ->description('Real‑time PV power level')
                ->descriptionIcon('heroicon-m-bolt')
                ->color('warning'),
            Stat::make('Last Update', $latest->recorded_at->timezone('Asia/Phnom_Penh')->diffForHumans())
                ->description($latest->recorded_at->timezone('Asia/Phnom_Penh')->toDateTimeString()),
        ];
    }
}
