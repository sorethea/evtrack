<?php

namespace App\Filament\Resources\EvLogResource\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Number;
require_once('app/Helpers/ev.php');
class VehicleOverview extends BaseWidget
{
    protected function getStats(): array
    {
        $vehicle = auth()->user()->vehicle;
        $log = $vehicle->latestLog;
        return [
            Stat::make(trans('ev.odo'),Number::format(get_log_item_value($log,1)).'km'),
            Stat::make(trans('ev.soc'),Number::format($log->items->where('item_id',11)->value('value')).'%'),
            Stat::make(trans('ev.accumulative').' '.trans('ev.charge'),Number::format($log->items->where('item_id',19)->value('value')).'kWh'),
            Stat::make(trans('ev.accumulative').' '.trans('ev.discharge'),Number::format($log->items->where('item_id',20)->value('value')).'kWh'),
        ];
    }
}
