<?php

namespace App\Filament\Resources\ChargeResource\Widgets;

use App\Models\Charge;
use Filament\Resources\Resource;
use Filament\Widgets\ChartWidget;

class ChargeCost extends ChartWidget
{
    protected static ?string $heading = 'EV monthly charging cost';

    protected function getData(): array
    {
        $rate = config("ev.usd_rate");
        $data = Charge::selectRaw("MONTHNAME(charges.date) AS `month`,MONTH(charges.date) AS `month_num`,SUM(ROUND(price * qty/{$rate},2)) AS `cost`")
            ->where('date','>=',now()->subMonth(12))
            ->groupBy(['month','month_num'])
            ->orderBy('month_num')
            ->pluck('cost','month');

        return [
            'datasets' => [
                [
                    'label' => 'Charging Cost',
                    'data' => $data->values()->toArray(),
                ],
            ],
            'labels' => $data->keys()->toArray(),
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }

    protected function getOptions(): array
    {
        return [
            'scales' => [
                'y' => [
                    'beginAtZero' => true,
                    'ticks' => [
                        'precision' => 0
                    ]
                ]
            ]
        ];
    }
}
