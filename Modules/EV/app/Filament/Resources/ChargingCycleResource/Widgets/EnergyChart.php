<?php

namespace Modules\EV\Filament\Resources\ChargingCycleResource\Widgets;

use Filament\Widgets\ChartWidget;
use Illuminate\Database\Eloquent\Model;

class EnergyChart extends ChartWidget
{
    public Model $record;
    protected static ?string $heading = 'Accumulative Energy';

    protected function getData(): array
    {
        $socArray = $this->record->logs->pluck('soc')->toArray();
        $chargeArray = $this->record->logs->pluck('ac')->toArray();
        $dischargeArray = $this->record->logs->pluck('ad')->toArray();
        //$lvcArray = $this->record->logs->pluck('lvc')->toArray();
        return [

            'datasets'=>[
                [
                    'label'=>'Charge',
                    'borderColor' => '#8B5CF6',
                    'backgroundColor' => 'rgba(139, 92, 246, 0.2)',
                    'data'=>$chargeArray,
                ],
                [
                    'label'=>'Discharge',
                    'borderColor' => '#F59E0B',
                    'backgroundColor' => 'rgba(245, 158, 11, 0.2)',
                    'data'=>$dischargeArray,
                ],
//                [
//                    'label'=>'Lowest',
//                    'borderColor' => '#F59E0B',
//                    'backgroundColor' => 'rgba(245, 158, 11, 0.2)',
//                    'data'=>$lvcArray,
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
}
