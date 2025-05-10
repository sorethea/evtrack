<?php

namespace App\Filament\Resources\ChargeResource\Widgets;

use App\Models\Charge;
use Filament\Widgets\Concerns\InteractsWithPageTable;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Number;

class ChargeOverview extends BaseWidget
{
    use InteractsWithPageTable;
    protected function getStats(): array
    {
        $total = Charge::selectRaw('SUM(`qty`*`price`) as `cost`')->value('cost');
        $currency = config("ev.currency");
        $rate = config("ev.usd_rate");
        $total_cost = round($total/$rate,2);
        $total_cost = Number::currency($total_cost,$currency);
        return [
            Stat::make("Total Charging Cost", $total_cost)->color('success'),
        ];
    }
}
