<?php

namespace Modules\EV\Filament\Resources\ChargingCycleResource\Widgets;

use Filament\Widgets\ChartWidget;

class VoltageChart extends ChartWidget
{
    protected static ?string $heading = 'Chart';

    protected function getData(): array
    {
        return [
            //
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}
