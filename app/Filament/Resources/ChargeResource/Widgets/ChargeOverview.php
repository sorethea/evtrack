<?php

namespace App\Filament\Resources\ChargeResource\Widgets;

use Filament\Actions\Concerns\InteractsWithRecord;
use Filament\Widgets\Concerns\InteractsWithPageTable;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class ChargeOverview extends BaseWidget
{
    use InteractsWithRecord;
    protected function getStats(): array
    {
        return [
            Stat::make("Total Charging Energy", 100)
        ];
    }
}
