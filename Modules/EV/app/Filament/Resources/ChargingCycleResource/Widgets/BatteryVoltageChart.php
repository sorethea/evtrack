<?php

namespace Modules\EV\Filament\Resources\ChargingCycleResource\Widgets;

use Filament\Widgets\ChartWidget;
use Illuminate\Database\Eloquent\Model;

class BatteryVoltageChart extends ChartWidget
{
    public Model $record;
    protected static ?string $heading = 'Voltage';
    protected function getData(): array
    {
        $socArray = $this->record->logs->pluck('soc')->toArray();
        $voltageArray = $this->record->logs->pluck('voltage')->toArray();

        return [
            'datasets'=>[
                [
                    'label'=>'Battery',
                    'borderColor' => '#3B82F6',
                    'backgroundColor' => 'rgba(59, 130, 246, 0.2)',
                    'data'=>$voltageArray,
                ],
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
//                    'ticks' => [
//                        'precision' => 0
//                    ]
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
