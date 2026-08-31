<?php

namespace Modules\SA\Filament\Widgets;

use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Modules\SA\Models\Metric;

class Battery extends StatsOverviewWidget
{
    protected static ?string $pullingInterval ='10s';
    protected function getStats(): array
    {
        $latest = Metric::latest('recorded_at')->first();
        $soc = $latest ? ($latest->metadata['battery_soc'] ?? '--') : '--';
        return [
            Stat::make('Battery SoC', $soc . '%')
                ->description('Real‑time battery level')
                ->descriptionIcon('heroicon-m-battery-100')
                ->color('success'),
        ];
    }
}
