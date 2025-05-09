<?php

namespace App\Filament\Resources\ChargeResource\Widgets;

use Filament\Widgets\ChartWidget;

class ChargeCost extends ChartWidget
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
