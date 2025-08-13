<?php

namespace Modules\EV\Filament\Resources\ChargingCycleResource\Widgets;

use Filament\Widgets\ChartWidget;
use Illuminate\Database\Eloquent\Model;

class VoltageChart extends ChartWidget
{
    public Model $record;
    protected static ?string $heading = 'Voltage vs. State of Charge';
    protected function getData(): array
    {
        $socArray = $this->record->logs->pluck('soc')->toArray();
        $voltageArray = $this->record->logs->pluck('voltage')->toArray();
        $distanceArray = $this->record->logs->pluck('distance')->toArray();

        return [
            'datasets'=>[
                [
                    'label'=>'Voltage',
                    'data'=>$voltageArray,
                ],
                [
                    'label'=>'Distance',
                    'data'=>$distanceArray,
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
