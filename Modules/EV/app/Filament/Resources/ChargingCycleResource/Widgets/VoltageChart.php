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

        return [
            'dataset'=>[
                [
                    'label'=>'State of Charge',
                    'data'=>$socArray,
                ],
            ],

        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}
