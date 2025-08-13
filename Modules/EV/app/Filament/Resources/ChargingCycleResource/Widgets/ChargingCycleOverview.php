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
        $consumptionArray = $this->record->logs->pluck('consumption')->toArray();
        $from_date = Carbon::parse($this->record->cycle_date)->format('d M, Y');
        $to_date = Carbon::parse($this->record->end_date)->format('d M, Y');
        //dd($this->record);
        return [

            Stat::make('Current State of Charge',Number::format($this->record->last_soc??0).'%')
                ->icon('heroicon-o-battery-50')
                ->color('danger')
                ->description("Battery from {$this->record->root_soc}% to {$this->record->last_soc}%")
                ->chart($socArray),
            Stat::make('Consumption',Number::format($this->record->consumption??0).'kWh/100km')
                ->icon('heroicon-o-bolt')
                ->color('warning')
                ->description("Gross discharge: {$this->record->charge}kWh & Regenerative Braking: {$this->record->regen}kWh")
                ->chart($consumptionArray),
            Stat::make('Distance',Number::format($this->record->distance??0).'km')
                ->icon('heroicon-o-map')
                ->color('success')
                ->description("From {$from_date} to {$to_date}")
                ->chart($distancesArray),
        ];
    }
}
