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
        $socArray = $this->record->logs->pluck('ac')->toArray();
        $chargeArray = $this->record->logs->pluck('ad')->toArray();
        $dischargeArray = $this->record->logs->pluck('hvc')->toArray();
        //$lvcArray = $this->record->logs->pluck('lvc')->toArray();
        return [

            'datasets'=>[
                [
                    'label'=>'Voltage',
                    'borderColor' => '#3B82F6',
                    'backgroundColor' => 'rgba(59, 130, 246, 0.2)',
                    'data'=>$chargeArray,
                ],
                [
                    'label'=>'Highest',
                    'borderColor' => '#8B5CF6',
                    'backgroundColor' => 'rgba(139, 92, 246, 0.2)',
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
}
