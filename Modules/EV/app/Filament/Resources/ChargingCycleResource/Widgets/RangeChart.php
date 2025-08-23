<?php

namespace Modules\EV\Filament\Resources\ChargingCycleResource\Widgets;

use Filament\Widgets\ChartWidget;
use Illuminate\Database\Eloquent\Model;

class RangeChart extends ChartWidget
{
    public Model $record;
    protected static ?string $heading = 'Range vs. Capacity';

    protected function getData(): array
    {
        $rangeArray = $this->record->logs->pluck('range')->toArray();
        $capacityArray = $this->record->logs->pluck('capacity')->toArray();
        return [

            'datasets'=>[
                [
                    'label'=>'Range',
                    'borderColor' => '#8B5CF6',
                    'backgroundColor' => 'rgba(139, 92, 246, 0.2)',
                    'data'=>$rangeArray,
                ],
                [
                    'label'=>'Capacity',
                    'borderColor' => '#F59E0B',
                    'backgroundColor' => 'rgba(245, 158, 11, 0.2)',
                    'data'=>$capacityArray,
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
