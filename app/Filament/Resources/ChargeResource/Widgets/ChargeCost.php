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
        $data = Charge::selectRaw("YEAR(charges.date) AS `year`, MONTHNAME(charges.date) AS `month`,MONTH(charges.date) AS `month_num`,SUM(ROUND(price * qty/{$rate},2)) AS `cost`,SUM(qty)AS `energy`")
            ->where('date','>=',now()->subMonth(12))
            ->where('type','=','ac')
            ->groupBy(['year','month','month_num'])
            ->orderBy('year')
            ->orderBy('month_num')
            ->get();
        $labels = collect();
        $current = now()->startOfMonth()->subMonths(11);
        for ($i = 0; $i < 12; $i++) {
            $labels->push($current->format('F Y'));
            $current->addMonth();
        }
        $energyData = $this->mapDataToLabels($data, 'energy', $labels);
        $costData = $this->mapDataToLabels($data, 'cost', $labels);
        return [
            'labels' => $labels->toArray(),
            'datasets' => [
                [
                    'label' => 'Energy Consumption (kWh)',
                    'data' => $energyData,
                    'borderColor' => '#10b981', // Green
                    'backgroundColor' => '#10b98120',
                    'tension' => 0.4,
                ],
                [
                    'label' => 'Charging Cost (' . config('ev.currency') . ')',
                    'data' => $costData,
                    'borderColor' => '#3b82f6', // Blue
                    'backgroundColor' => '#3b82f620',
                    'tension' => 0.4,
                ],
            ],
        ];
    }

    private function mapDataToLabels($data, $metric, $labels)
    {
        $mappedData = $data->mapWithKeys(fn ($item) => [
            $item->month . ' ' . $item->year => $item->{$metric}
        ]);

        return $labels->map(fn ($label) => $mappedData[$label] ?? 0)->values();
    }

    protected function getType(): string
    {
        return 'line';
    }

    protected function getOptions(): array
    {
        return [
//            'plugins' => [
//                'legend' => [
//                    'position' => 'top',
//                ],
//                'tooltip' => [
//                    'callbacks' => [
//                        'label' => 'function(context) {
//                            return context.dataset.label + ": " + context.parsed.y.toLocaleString();
//                        }'
//                    ]
//                ]
//            ],
            'scales' => [
                'y' => [
                    'title' => [
                        'display' => true,
                        'text' => 'Value'
                    ],
                    'beginAtZero' => true,
                    'ticks' => [
                        'callback' => 'function(value) {
                            if (context.datasetIndex === 0) {
                                return value + " kWh";
                            }
                            return "' . config('ev.currency_symbol') . '" + value;
                        }'
                    ]
                ],
                'x' => [
                    'grid' => [
                        'display' => false
                    ]
                ]
            ]
        ];
    }
}
