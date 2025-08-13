<?php

namespace Modules\EV\Filament\Resources\ChargingCycleResource\Widgets;

use Filament\Widgets\ChartWidget;
use Illuminate\Database\Eloquent\Model;

class VoltageChart extends ChartWidget
{
    public Model $record;
    protected static ?string $heading = 'Voltage Trench';
    protected function getData(): array
    {
        $socArray = $this->record->logs->pluck('soc')->toArray();
        $voltageArray = $this->record->logs->pluck('av_voltage')->toArray();
        $hvcArray = $this->record->logs->pluck('hvc')->toArray();
        $lvcArray = $this->record->logs->pluck('lvc')->toArray();

        return [
            'datasets'=>[
                [
                    'label'=>'Cell Voltage',
                    'data'=>$voltageArray,
                ],
                [
                    'label'=>'Cell Highest Voltage',
                    'data'=>$hvcArray,
                ],
                [
                    'label'=>'Cell Lowest Voltage',
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
}
