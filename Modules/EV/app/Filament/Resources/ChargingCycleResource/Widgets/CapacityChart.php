<?php

namespace Modules\EV\Filament\Resources\ChargingCycleResource\Widgets;

use Filament\Widgets\ChartWidget;
use Illuminate\Database\Eloquent\Model;

class CapacityChart extends ChartWidget
{
    public Model $record;
    protected static ?string $heading = 'Capacity';

    protected function getData(): array
    {
        $socArray = $this->record->logs->pluck('soc')->toArray();
        $chargeArray = $this->record->logs->pluck('charge')->toArray();
        $dischargeArray = $this->record->logs->pluck('discharge')->toArray();
        $distanceArray = $this->record->logs->pluck('distance')->toArray();
        return [

            'datasets'=>[
                [
                    'label'=>'Added',
                    'borderColor' => '#8B5CF6',
                    'backgroundColor' => 'rgba(139, 92, 246, 0.2)',
                    'data'=>$chargeArray,
                ],
                [
                    'label'=>'Used',
                    'borderColor' => '#F59E0B',
                    'backgroundColor' => 'rgba(245, 158, 11, 0.2)',
                    'data'=>$dischargeArray,
                ],
                [
                    'label'=>'Distance',
                    'borderColor' => '#3B82F6',
                    'backgroundColor' => 'rgba(59, 130, 246, 0.2)',
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
