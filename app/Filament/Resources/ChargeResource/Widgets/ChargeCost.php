<?php

namespace App\Filament\Resources\ChargeResource\Widgets;

use App\Models\Charge;
use Filament\Resources\Resource;
use Filament\Support\RawJs;
use Filament\Widgets\ChartWidget;
use Filament\Widgets\Concerns\InteractsWithPageTable;
use Illuminate\Contracts\Support\Htmlable;

class ChargeCost extends ChartWidget
{
    use InteractsWithPageTable;
    protected static ?string $heading = ' EV Charging Cost (USD)';
//    public function getHeading(): string|Htmlable|null
//    {
//        $rate = config("ev.usd_rate");
//        $total = Charge::selectRaw("SUM(ROUND(price * qty/{$rate},2)) AS `grand_total`")
//            ->where('date','>=',now()->subMonth(12))
//            ->value('grand_total');
//        return 'EV charging cost for the last 12 months ($'.$total.')';
//    }

    protected function getData(): array
    {
        $rate = config("ev.usd_rate");
        $data = Charge::selectRaw("YEAR(charges.date) AS `year`, DATE_FORMAT(charges.date,'%b') AS `month`,MONTH(charges.date) AS `month_num`,SUM(ROUND(price * qty/{$rate},2)) AS `cost`,SUM(ROUND(qty,0))AS `energy`")
            ->where('date','>=',now()->subMonth(12))
            ->groupBy(['year','month','month_num'])
            ->orderBy('year')
            ->orderBy('month_num')
            ->get();
        $acData = Charge::selectRaw("YEAR(charges.date) AS `year`, DATE_FORMAT(charges.date,'%b') AS `month`,MONTH(charges.date) AS `month_num`,SUM(ROUND(price * qty/{$rate},2)) AS `cost`,SUM(ROUND(qty,0))AS `energy`")
            ->where('date','>=',now()->subMonth(12))
            ->where('type','=','ac')
            ->groupBy(['year','month','month_num'])
            ->orderBy('year')
            ->orderBy('month_num')
            ->get();
        $dcData = Charge::selectRaw("YEAR(charges.date) AS `year`, DATE_FORMAT(charges.date,'%b') AS `month`,MONTH(charges.date) AS `month_num`,SUM(ROUND(price * qty/{$rate},2)) AS `cost`,SUM(ROUND(qty,0))AS `energy`")
            ->where('date','>=',now()->subMonth(12))
            ->where('type','=','dc')
            ->groupBy(['year','month','month_num'])
            ->orderBy('year')
            ->orderBy('month_num')
            ->get();
        $labels = collect();
        $current = now()->startOfMonth()->subMonths(11);
        for ($i = 0; $i < 12; $i++) {
            $labels->push($current->format('M Y'));
            $current->addMonth();
        }

        $costData = $this->mapDataToLabels($data, 'cost', $labels);
        $costAcData = $this->mapDataToLabels($acData, 'cost', $labels);
        $costDcData = $this->mapDataToLabels($dcData, 'cost', $labels);
        return [
            'labels' => $labels->toArray(),
            'datasets' => [
                [
                    'label' => 'Charging Cost',
                    'data' => $costData,
                    'borderColor' => '#ef4444', // Red
                    'backgroundColor' => '#fca5a5',
                    'pointBackgroundColor' => '#fca5a5',
                    'tension' => 0.4,
                ],
                [
                    'label' => 'Home Charging Cost',
                    'data' => $costAcData,
                    'borderColor' => '#10b981', // Green
                    'backgroundColor' => '#10b98120',
                    'pointBackgroundColor' => '#10b98120',
                    'tension' => 0.4,
                ],
                [
                    'label' => 'Fast Charging Cost',
                    'data' => $costDcData,
                    'borderColor' => '#3b82f6', // Blue
                    'backgroundColor' => '#3b82f620',
                    'pointBackgroundColor' => '#3b82f620',
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

    protected function getOptions(): RawJs
    {
        $currency = config("ev.currency_symbol");
        return RawJs::make(<<<JS
            {
                plugins:{
                    legend:{
                        animation: false,
                        position: 'top'
                    },
                    tooltip:{
                        callbacks:{
                            label:  function (context){
                                    const value = context.parsed.y || 0;
                                    return context.dataset.label + ': {$currency}'+ value.toLocaleString();
                            },
                            footer: function(context){
                                let total = 0;
                                context[0].dataset.data.forEach(value => {
                                    total += Number(value) || 0;
                                });
                                return 'Grand Total: {$currency}' + total.toLocaleString();
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        display: true,
                        grid: {
                            display: false
                        },

                    },
                    x: {
                        grid: {
                            display: false
                        }

                    }
                }
            }
        JS);
    }
}
