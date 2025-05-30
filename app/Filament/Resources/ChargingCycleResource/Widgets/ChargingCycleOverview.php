<?php

namespace App\Filament\Resources\ChargingCycleResource\Widgets;

use Filament\Forms\Get;
use Filament\Resources\Pages\Concerns\InteractsWithRecord;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class ChargingCycleOverview extends BaseWidget
{
    use InteractsWithRecord;

    protected function getStats(): array
    {
        dd($this->getRecord());
        return [
            Stat::make('Distance','100km')
        ];
    }
}
