<?php

namespace Modules\SA\Filament\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Modules\SA\Models\Metric;

class EnergyStats extends BaseWidget
{
    protected static ?string $pollInterval = '5s';

    protected function getStats(): array
    {
        $records = Metric::latest('recorded_at')->take(2)->get();
        $latest = $records->first();
        $previous = $records->count() > 1 ? $records->get(1) : null;

        if (!$latest) {
            return [Stat::make('No data', '--')];
        }

        $data = $latest->metadata;

        // Helper to get numeric value
        $get = fn($key) => is_numeric($data[$key] ?? null) ? (float)$data[$key] : 0;

        // --- Battery trend (charge/discharge) ---
        $trendText = 'Stable';
        $trendColor = 'gray';
        $trendIcon = 'heroicon-m-minus';
        $rate = 0;

        if ($previous && isset($data['total/battery_state_of_charge']) && isset($previous->metadata['total/battery_state_of_charge'])) {
            $socNow = (float)$data['total/battery_state_of_charge'];
            $socPrev = (float)$previous->metadata['total/battery_state_of_charge'];
            $timeDiff = $latest->recorded_at->diffInSeconds($previous->recorded_at);
            if ($timeDiff > 0) {
                $rate = (($socNow - $socPrev) / $timeDiff) * 3600; // % per hour
            }
        }

        if ($rate > 0.1) {
            $trendText = 'Charging +' . number_format($rate, 1) . '%/hr';
            $trendColor = 'success';
            $trendIcon = 'heroicon-m-arrow-trending-up';
        } elseif ($rate < -0.1) {
            $trendText = 'Discharging ' . number_format($rate, 1) . '%/hr';
            $trendColor = 'danger';
            $trendIcon = 'heroicon-m-arrow-trending-down';
        } else {
            $trendText = 'Stable';
            $trendColor = 'gray';
            $trendIcon = 'heroicon-m-minus';
        }

        return [
            // Battery
            Stat::make('Battery', number_format($get('total/battery_state_of_charge'), 1) . '%')
                ->description($trendText)
                ->color($trendColor)
                ->descriptionIcon($trendIcon),

            // Solar PV
            Stat::make('Solar PV', number_format($get('total/pv_power'), 0) . ' W')
                ->description('Today: ' . number_format($get('total/pv_energy'), 1) . ' kWh')
                ->descriptionIcon('heroicon-m-bolt')
                ->color('warning'),

            // Load
            Stat::make('Load', number_format($get('total/load_power'), 0) . ' W')
                ->description('Active consumption')
                ->descriptionIcon('heroicon-m-home')
                ->color('info'),

            // Grid
            Stat::make('Grid', number_format($get('total/grid_power'), 0) . ' W')
                ->description($get('total/grid_power') == 0 ? 'Idle' : 'Import/Export')
                ->descriptionIcon('heroicon-m-arrow-path')
                ->color('danger'),
        ];
    }
}
