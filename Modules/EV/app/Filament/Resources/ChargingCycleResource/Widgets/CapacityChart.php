<?php

namespace Modules\EV\Filament\Resources\ChargingCycleResource\Widgets;

use Filament\Widgets\ChartWidget;
use Illuminate\Database\Eloquent\Model;

class CapacityChart extends ChartWidget
{
    public Model $record;
    protected static ?string $heading = 'Capacity';

    protected function getData(): array
    {
        $socArray = $this->record->logs->pluck('soc')->toArray();
        $chargeArray = $this->record->logs->pluck('charge')->toArray();
        $dischargeArray = $this->record->logs->pluck('discharge')->toArray();
        $distanceArray = $this->record->logs->pluck('distance')->toArray();
        $aCharges = [];
        $aCharge = 0;
        foreach ($chargeArray as $value){
            $aCharge +=$value;
            $aCharges[]=$aCharge;
        }
        $aDischarges = [];
        $aDischarge = 0;
        foreach ($dischargeArray as $value){
            $aDischarge +=$value;
            $aDischarges[]=$aDischarge;
        }
        $aDistances = [];
        $aDistance = 0;
        foreach ($distanceArray as $value){
            $aDistance +=$value;
            $aDistances[]=$aDistance;
        }
        return [

            'datasets'=>[
                [
                    'label'=>'Added',
                    'borderColor' => '#8B5CF6',
                    'backgroundColor' => 'rgba(139, 92, 246, 0.2)',
                    'data'=>$aCharges,
                ],
                [
                    'label'=>'Used',
                    'borderColor' => '#F59E0B',
                    'backgroundColor' => 'rgba(245, 158, 11, 0.2)',
                    'data'=>$aDischarges,
                ],
                [
                    'label'=>'Distance',
                    'borderColor' => '#3B82F6',
                    'backgroundColor' => 'rgba(59, 130, 246, 0.2)',
                    'data'=>$aDistances,
                ],
            ],
            'labels'=>$socArray,
        ];
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
                        'precision' => 0
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

    protected function getType(): string
    {
        return 'line';
    }
}
