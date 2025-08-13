<?php

namespace Modules\EV\Filament\Resources\ChargingCycleResource\Widgets;

use Filament\Widgets\ChartWidget;
use Illuminate\Database\Eloquent\Model;

class TemperatureChart extends ChartWidget
{
    public Model $record;
    protected static ?string $heading = 'Temperature';
    protected function getData(): array
    {
        $socArray = $this->record->logs->pluck('soc')->toArray();
        $tempArray = $this->record->logs->pluck('tc')->toArray();
        $htcArray = $this->record->logs->pluck('htc')->toArray();
        $ltcArray = $this->record->logs->pluck('ltc')->toArray();

        return [
            'datasets'=>[
                [
                    'label'=>'Battery',
                    'borderColor' => '#3B82F6',
                    'backgroundColor' => 'rgba(59, 130, 246, 0.2)',
                    'data'=>$tempArray,
                ],
                [
                    'label'=>'Highest',
                    'borderColor' => '#8B5CF6',
                    'backgroundColor' => 'rgba(139, 92, 246, 0.2)',
                    'data'=>$htcArray,
                ],
                [
                    'label'=>'Lowest',
                    'borderColor' => '#F59E0B',
                    'backgroundColor' => 'rgba(245, 158, 11, 0.2)',
                    'data'=>$ltcArray,
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
