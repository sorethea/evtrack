<?php

namespace Modules\SA\Filament\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Modules\SA\Models\Metric;

class EnergyStats extends BaseWidget
{
    protected static ?string $pollingInterval = '5s';

    protected function getStats(): array
    {
        $latest = Metric::latest('recorded_at')->first();

        if (!$latest) {
            return [
                Stat::make('No data', '--')
            ];
        }

        $data = $latest->metadata;

        // Helper to get numeric value
        $get = fn($key) => is_numeric($data[$key] ?? null) ? (float)$data[$key] : 0;

        // Battery power (sign)
        $batteryPower = $get('total/battery_power');
        $batteryPowerDisplay = ($batteryPower >= 0 ? '+' : '') . number_format($batteryPower, 0) . ' W';
        $batteryColor = $batteryPower > 0 ? 'success' : ($batteryPower < 0 ? 'danger' : 'gray');
        $batteryIcon = $batteryPower > 0 ? 'heroicon-m-arrow-trending-up' : ($batteryPower < 0 ? 'heroicon-m-arrow-trending-down' : 'heroicon-m-minus');

        // Grid details
        $gridPower = $get('total/grid_power');
        $gridIn = $get('total/grid_energy_in');
        $gridOut = $get('total/grid_energy_out');

        $gridDescription = '';
        if ($gridPower == 0) {
            $gridDescription = 'Idle';
        } elseif ($gridPower > 0) {
            $gridDescription = 'Import: ' . number_format($gridPower, 0) . ' W · Today: ' . number_format($gridIn, 1) . ' kWh';
        } else {
            $gridDescription = 'Export: ' . number_format(abs($gridPower), 0) . ' W · Today: ' . number_format($gridOut, 1) . ' kWh';
        }

        return [
            // Solar PV
            Stat::make('Solar PV', number_format($get('total/pv_power'), 0) . ' W')
                ->description('Today: ' . number_format($get('total/pv_energy'), 1) . ' kWh')
                ->descriptionIcon('heroicon-m-bolt')
                ->color('warning'),

            // Battery
            Stat::make('Battery', number_format($get('total/battery_state_of_charge'), 1) . '%')
                ->description(
                    number_format($get('total/battery_voltage'), 1) . ' V · ' . $batteryPowerDisplay
                )
                ->descriptionIcon($batteryIcon)
                ->color($batteryColor),

            // Grid
            Stat::make('Grid', number_format($gridPower, 0) . ' W')
                ->description($gridDescription)
                ->descriptionIcon('heroicon-m-arrow-path')
                ->color('danger'),

            // Load
            Stat::make('Load', number_format($get('total/load_power'), 0) . ' W')
                ->description('Today: ' . number_format($get('total/load_energy'), 1) . ' kWh')
                ->descriptionIcon('heroicon-m-home')
                ->color('info'),
        ];
    }
}
