<?php

namespace Modules\SA\Filament\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Modules\SA\Models\Metric;

class Battery extends BaseWidget
{
    protected function getStats(): array
    {
        $latest = Metric::latest('recorded_at')->first();

        if (!$latest) {
            return [
                Stat::make('Battery SoC', '--')
                    ->description('No data yet'),
            ];
        }

        $metadata = $latest->metadata;

        // Helper to get numeric value safely
        $getNumeric = function ($key) use ($metadata) {
            $value = $metadata[$key] ?? null;
            return is_numeric($value) ? (float) $value : 0;
        };

        $batterySoc = $getNumeric('total/battery_state_of_charge');
        $pvEnergy   = $getNumeric('total/pv_energy');
        $temperature = $getNumeric('weather/outside_temperature');

        return [
            Stat::make('Battery SoC', number_format($batterySoc, 1) . '%')
                ->description('Main battery')
                ->descriptionIcon('heroicon-m-battery-100')
                ->color('success'),

            Stat::make('PV Energy', number_format($pvEnergy, 1) . ' kWh')
                ->description('Generated today')
                ->descriptionIcon('heroicon-m-bolt')
                ->color('warning'),

            Stat::make('Temperature', number_format($temperature, 1) . '°C')
                ->description('Outside')
                ->descriptionIcon('heroicon-o-thermometer')   // ✅ changed from 'm-thermometer'
                ->color('info'),

            Stat::make('Last Update', $latest->recorded_at->timezone('Asia/Phnom_Penh')->diffForHumans())
                ->description($latest->recorded_at->timezone('Asia/Phnom_Penh')->toDateTimeString()),
        ];
    }
}
