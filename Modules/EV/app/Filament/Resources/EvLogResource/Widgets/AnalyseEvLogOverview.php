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
            $cycleSoCMiddle = Number::format($this->record->cycleView->soc_middle,1);

            $cycleNetDischarge = $cycleDischarge -$cycleCharge;
            $cycleEnergyMiddle = $cycleNetDischarge + Number::format($this->record->cycleView->middle,0);
            $dischargeArray = $this->record->cycleView->logs->pluck('discharge')->toArray();
            $chargeArray = $this->record->cycleView->logs->pluck('charge')->toArray();
            $middleEnergyArray = $this->record->cycleView->logs->pluck('middle')->toArray();
            $middleSoCArray = $this->record->cycleView->logs->pluck('soc_middle')->toArray();
            $voltageArray = $this->record->cycleView->logs->pluck('av_voltage')->toArray();
            $voltageSpreadArray = $this->record->cycleView->logs->pluck('v_spread')->toArray();
            $highestCellVoltageArray = $this->record->cycleView->logs->pluck('hvc')->toArray();
            $cycleRootVoltage = Number::format($this->record->cycleView->root_voltage,1);
            $voltageSpread = Number::format($this->record->detail->v_spread*1000);
            $rootVoltageSpread = Number::format($this->record->cycle->v_spread *1000);
            $netEnergyArray = array_map(function ($v1,$v2){
                return $v1-$v2;
            },$dischargeArray,$chargeArray);
            $nextEnergyAdded = $this->record->child?->detail?->charge??0;
            $currentSoCIcon = '';
            $currentSoC = $this->record->detail->soc;
            if ($currentSoC==100){
                $currentSoCIcon =  'custom-battery-full';
            }elseif($currentSoC<100 && $currentSoC>=50){
                $currentSoCIcon =  'custom-battery-good';
            }elseif($currentSoC<50 && $currentSoC>=20) {
                $currentSoCIcon = 'custom-battery-low';
            }elseif($currentSoC<20 && $currentSoC>=10){
                $currentSoCIcon = 'custom-battery-caution';
            }else{
                $currentSoCIcon = 'custom-battery-empty-exclamation';
            }
            return [
                Stat::make('Current SoC',Number::format($this->record->detail->soc??0,1).'%')
                    ->icon($currentSoCIcon)
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
                    ->icon('custom-battery-good')
                    ->color(Color::Emerald)
                    ->description("Cycle energy used: {$cycleDischarge} kWh")
                    ->chart($dischargeArray),
                Stat::make('Energy Added',Number::format($this->record->detail->charge??0,0).'kWh')
                    ->icon('custom-battery-good-charging')
                    ->color(Color::Sky)
                    ->description("Cycle energy added: {$cycleCharge} kWh")
                    ->chart($chargeArray),
                Stat::make('Net Energy Used',Number::format($this->record->detail->discharge-$this->record->detail->charge,0).'kWh')
                    ->icon('custom-battery-low')
                    ->color(Color::Purple)
                    ->description("Cycle net energy used: {$cycleNetDischarge} kWh")
                    ->chart($netEnergyArray),
                Stat::make('Estimated Energy To 100%',Number::format($cycleNetDischarge,0).'kWh')
                    ->icon('custom-battery-full')
                    ->color(Color::Indigo)
                    ->description("Cycle balance energy: {$cycleEnergyMiddle} kWh")
                    ->chart($middleEnergyArray),
                Stat::make('Next Energy Added',Number::format($nextEnergyAdded,0).'kWh')
                    ->icon('custom-battery-empty-charging')
                    ->color(Color::Fuchsia)
                    ->description("Cycle SoC middle: {$cycleSoCMiddle} %")
                    ->chart($middleSoCArray),
                Stat::make('Current Battery Voltage',Number::format($this->record->detail->voltage,0).'V')
                    ->icon('custom-volt')
                    ->color(Color::Pink)
                    ->description("Cycle Max Voltage: {$cycleRootVoltage} V")
                    ->chart($voltageArray),
                Stat::make('Highest Voltage Cell Value',Number::format($this->record->detail->hvc,3).'V')
                    ->icon('custom-battery-low-charging')
                    ->color(Color::Yellow)
                    ->description("Lowest voltage cell value: {$this->record->detail->lvc} V")
                    ->chart($highestCellVoltageArray),
                Stat::make('Current Voltage Delta',"{$voltageSpread} mV")
                    ->icon('custom-high-voltage-bolt')
                    ->color(Color::Cyan)
                    ->description("Cycle Max Voltage: {$rootVoltageSpread} mV")
                    ->chart($voltageSpreadArray),
            ];
        }
        return [];
    }

    protected function getColumns(): int
    {
        return 4;
    }
}
