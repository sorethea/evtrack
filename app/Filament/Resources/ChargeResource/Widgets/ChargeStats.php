<?php

namespace App\Filament\Resources\ChargeResource\Widgets;

use Filament\Widgets\Concerns\InteractsWithPageTable;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class ChargeStats extends BaseWidget
{
    use InteractsWithPageTable;
    protected function getStats(): array
    {
        $rate = config("ev.usd_rate");
        return [
            Stat::make('Total Charging Cost',$this->getPageTableQuery()->selectRaw("SUM(ROUND(price * qty/{$rate},2)) AS `cost`")->sum("cost"))
        ];
    }
}
