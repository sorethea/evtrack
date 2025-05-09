<?php

namespace App\Filament\Resources\ChargeResource\Widgets;

use App\Models\Charge;
use Filament\Resources\Resource;
use Filament\Widgets\ChartWidget;

class ChargeCost extends ChartWidget
{
    protected static ?string $heading = 'Chart';

    protected function getData(): array
    {
        $data = Charge::selectRaw("MONTHNAME(charges.date) AS `month_name`,SUM(price * qty) AS `cost`")
            ->groupBy('month_name')
            ->pluck('cost','month_name');

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
}
