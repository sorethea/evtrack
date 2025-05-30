<?php

namespace App\Filament\Resources\ChargingCycleResource\Widgets;

use Filament\Forms\Get;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class ChargingCycleOverview extends BaseWidget
{

    protected function getStats(): array
    {
        dd($this->getViewData());
        return [
            Stat::make('Distance','100km')
        ];
    }
}
