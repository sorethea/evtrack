<?php

namespace Modules\EV\Filament\Resources\EvLogResource\Widgets;

use Filament\Support\Colors\Color;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Number;

class AnalyseEvLogOverview extends BaseWidget
{
    public Model $record;
    //protected ?string $heading = 'Overview';
    protected function getStats(): array
    {
        if($this->record->log_type=='driving'){

            $socArray = $this->record->cycleView->logs->pluck('soc')->toArray();
            $distancesArray = $this->record->cycleView->logs->pluck('distance')->toArray();
            $consumptionArray = $this->record->cycleView->logs->pluck('consumption')->toArray();
            $rangeArray = $this->record->cycleView->logs->pluck('range')->toArray();
            $cycleConsumption = Number::format($this->record->cycleView->consumption*10,0);
            $cycleDistance = Number::format($this->record->cycleView->distance,1);
            $cycleRange = Number::format($this->record->cycleView->range,0);
            $cycleDischarge = Number::format($this->record->cycleView->discharge,0);
            $cycleCharge = Number::format($this->record->cycleView->charge,0);
            $cycleNetDischarge = $cycleDischarge -$cycleCharge;
            $dischargeArray = $this->record->cycleView->logs->pluck('discharge')->toArray();
            $chargeArray = $this->record->cycleView->logs->pluck('charge')->toArray();
            $netEnergyArray = array_map(function ($v1,$v2){
                return $v1-$v2;
            },$dischargeArray,$chargeArray);
            return [
                Stat::make('Current SoC',Number::format($this->record->detail->soc??0,1).'%')
                    ->icon('heroicon-o-battery-50')
                    ->color('danger')
                    ->description("Cycle SoC from {$this->record->cycleView->root_soc}% to {$this->record->cycleView->last_soc}%")
                    ->chart($socArray),
                Stat::make('Consumption',Number::format($this->record->detail->consumption*10,0).' Wh/km')
                    ->icon('heroicon-o-bolt')
                    ->color('warning')
                    ->description("Cycle consumption: {$cycleConsumption} Wh/km")
                    ->chart($consumptionArray),
                Stat::make('Distance',Number::format($this->record->detail->distance??0,1).'km')
                    ->icon('heroicon-o-map')
                    ->color('success')
                    ->description("Cycle distance: {$cycleDistance} km")
                    ->chart($distancesArray),
                Stat::make('Range',Number::format($this->record->detail->range??0,0).'km')
                    ->icon('heroicon-o-map-pin')
                    ->color('info')
                    ->description("Cycle range: {$cycleRange} km")
                    ->chart($rangeArray),
                Stat::make('Gross Energy Used',Number::format($this->record->detail->discharge??0,0).'kWh')
                    ->icon('custom-battery-mid')
                    ->color(Color::Emerald)
                    ->description("Cycle energy used: {$cycleDischarge} kWh")
                    ->chart($dischargeArray),
                Stat::make('Energy Added',Number::format($this->record->detail->charge??0,0).'kWh')
                    ->icon('custom-battery-empty-charging')
                    ->color(Color::Sky)
                    ->description("Cycle energy added: {$cycleCharge} kWh")
                    ->chart($chargeArray),
                Stat::make('Net Energy Used',Number::format($this->record->detail->discharge-$this->record->detail->charge,0).'kWh')
                    ->icon('custom-battery')
                    ->color(Color::Purple)
                    ->description("Cycle net energy used: {$cycleNetDischarge} kWh")
                    ->chart($netEnergyArray),
            ];
        }
        return [];
    }
}
