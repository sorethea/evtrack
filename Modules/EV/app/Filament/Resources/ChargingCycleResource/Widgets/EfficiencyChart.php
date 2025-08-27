<?php

namespace Modules\EV\Filament\Resources\ChargingCycleResource\Widgets;

use Filament\Widgets\ChartWidget;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Number;

class EfficiencyChart extends ChartWidget
{
    public Model $record;
    protected static ?string $heading = 'Energy Efficiency';

    protected function getData(): array
    {
        $socArray = $this->record->logs->pluck('soc')->toArray();
        $socUsedArray = $this->record->logs->pluck('soc_derivation')->toArray();
        $batteryCapacity = $this->record->vehicle->capacity;
        foreach ($socUsedArray as &$value){
            $value = Number::format($batteryCapacity * $value/100,1);
        }
        $usedEnergyArray = $this->record->logs->pluck('used_energy')->toArray();

        $effectiveUsedEnergyArray = array_map(function ($v1,$v2){
            if($v2>0){
                return Number::format(100*($v1/$v2),0);
            }
            return 0;
        },$socUsedArray,$usedEnergyArray);

        return [

            'datasets'=>[
                [
                    'label'=>'SoC vs Accumulative (%)',
                    'borderColor' => '#8B5CF6',
                    'backgroundColor' => 'rgba(139, 92, 246, 0.2)',
                    'data'=>$effectiveUsedEnergyArray,
               ],
//                [
//                    'label'=>'Accumulative',
//                    'borderColor' => '#F59E0B',
//                    'backgroundColor' => 'rgba(245, 158, 11, 0.2)',
//                    'data'=>$usedEnergyArray,
//                ],
            ],
            'labels'=>$socArray,
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }

    protected function getOptions(): array
    {
        return [
            'plugins' => [
                'legend' => [
                    'position' => 'bottom',
                    'labels' => [
                        'font' => [
                            'size' => 14,
                            'family' => "'Inter', sans-serif"
                        ]
                    ]
                ],
            ],
            'scales' => [
                'y' => [
                    'beginAtZero' => false,
                    'grid' => [
                        'drawBorder' => false,
                    ],
                    'ticks' => [
                        'precision' => 1
                    ]
                ],
                'x' => [
                    'grid' => [
                        'display' => false,
                    ]
                ]
            ],
            'interaction' => [
                'mode' => 'index',
                'intersect' => false,
            ],
            'maintainAspectRatio' => true,
        ];
    }
}
