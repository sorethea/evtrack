<?php

namespace Modules\EV\Filament\Resources\ChargingCycleResource\Widgets;

use Filament\Widgets\ChartWidget;
use Illuminate\Database\Eloquent\Model;

class ConsumptionChart extends ChartWidget
{
    public Model $record;
    protected static ?string $heading = 'Consumption';
    protected function getData(): array
    {
        $socArray = $this->record->logs->pluck('soc')->toArray();
        $consumptionArray = $this->record->logs->pluck('consumption')->toArray();
        $aConsumptionArray = $this->record->logs->pluck('a_consumption')->toArray();
        $regenArray = $this->record->logs->pluck('100_charge')->toArray();

        return [
            'datasets'=>[
                [
                    'label'=>'SoC',
                    'borderColor' => '#3B82F6',
                    'backgroundColor' => 'rgba(59, 130, 246, 0.2)',
                    'data'=>$consumptionArray,
                ],
                [
                    'label'=>'Accumulative',
                    'borderColor' => '#8B5CF6',
                    'backgroundColor' => 'rgba(139, 92, 246, 0.2)',
                    'data'=>$aConsumptionArray,
                ],
                [
                    'label'=>'Regen',
                    'borderColor' => '#F59E0B',
                    'backgroundColor' => 'rgba(245, 158, 11, 0.2)',
                    'data'=>$regenArray,
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
