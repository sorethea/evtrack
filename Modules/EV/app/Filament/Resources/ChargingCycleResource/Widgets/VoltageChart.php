<?php

namespace Modules\EV\Filament\Resources\ChargingCycleResource\Widgets;

use Filament\Widgets\ChartWidget;
use Illuminate\Database\Eloquent\Model;

class VoltageChart extends ChartWidget
{
    public Model $record;
    protected static ?string $heading = 'Voltage';
    protected function getData(): array
    {
        $socArray = $this->record->logs->pluck('soc')->toArray();
        $voltageArray = $this->record->logs->pluck('av_voltage')->toArray();
        $hvcArray = $this->record->logs->pluck('hvc')->toArray();
        $lvcArray = $this->record->logs->pluck('lvc')->toArray();

        return [
            'datasets'=>[
                [
                    'label'=>'Battery',
                    'borderColor' => '#3B82F6',
                    'backgroundColor' => 'rgba(59, 130, 246, 0.2)',
                    'data'=>$voltageArray,
                ],
                [
                    'label'=>'Highest',
                    'borderColor' => '#8B5CF6',
                    'backgroundColor' => 'rgba(139, 92, 246, 0.2)',
                    'data'=>$hvcArray,
                ],
                [
                    'label'=>'Lowest',
                    'borderColor' => '#F59E0B',
                    'backgroundColor' => 'rgba(245, 158, 11, 0.2)',
                    'data'=>$lvcArray,
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
                    'beginAtZero' => true,
                    'grid' => [
                        'drawBorder' => false,
                    ],
                    'ticks' => [
                        'precision' => 4
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
