<?php

namespace Modules\EV\Filament\Resources\ChargingCycleResource\Widgets;

use Carbon\Carbon;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Number;

class ChargingCycleOverview extends BaseWidget
{
    //use InteractsWithRecord;

    public Model $record;


    protected function getStats(): array
    {
        $distancesArray = $this->record->logs->pluck('distance')->toArray();
        $socArray = $this->record->logs->pluck('soc')->toArray();
        $from_date = Carbon::parse($this->record->cycle_date)->format('d M, Y');
        $to_date = Carbon::parse($this->record->end_date)->format('d M, Y');

        return [

            Stat::make('State of Charge',Number::format($this->record->to_soc??0).'km')
                ->icon('heroicon-o-map')
                ->color('success')
                ->description("From {$from_date} to {$to_date}")
                ->chart($socArray),
            Stat::make('Distance',Number::format($this->record->distance??0).'km')
                ->icon('heroicon-o-map')
                ->color('success')
                ->description("From {$from_date} to {$to_date}")
                ->chart($distancesArray),
        ];
    }
}
