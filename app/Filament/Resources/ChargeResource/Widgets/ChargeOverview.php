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
        $total_cost = Charge::sum(DB::raw('qty*price'));
        $rate = config("ev.usd_rate");
        return [
            Stat::make("Total Charging Cost", Number::currency($total_cost/$rate,config("ev.currency_symbol")) ),
        ];
    }
}
