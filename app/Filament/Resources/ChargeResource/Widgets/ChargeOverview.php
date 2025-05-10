<?php

namespace App\Filament\Resources\ChargeResource\Widgets;

use App\Models\Charge;
use Filament\Widgets\Concerns\InteractsWithPageTable;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class ChargeOverview extends BaseWidget
{
    use InteractsWithPageTable;
    protected function getStats(): array
    {
        $total_cost = Charge::selectRaw("(qty*price)AS cost")->sum('cost');
        return [
            Stat::make("Total Charging Cost", $total_cost)
        ];
    }
}
