<?php

namespace Modules\EV\Filament\Resources\EvLogResource\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Number;

class AnalyseEvLogOverview extends BaseWidget
{
    public Model $record;
    //protected ?string $heading = 'Overview';
    protected function getStats(): array
    {
        $socArray = $this->record->cycleView->logs->pluck('soc')->toArray();
        $distancesArray = $this->record->cycleView->logs->pluck('distance')->toArray();
        $consumptionArray = $this->record->cycleView->logs->pluck('consumption')->toArray();
        return [
            Stat::make('Current SoC',Number::format($this->record->detail->soc??0).'%')
                ->icon('heroicon-o-battery-50')
                ->color('danger')
                ->description("Battery from {$this->record->cycleView->root_soc}% to {$this->record->detail->soc}%")
                ->chart($socArray),
            Stat::make('Consumption',Number::format($this->record->detail->consumption*10,0).' Wh/km')
                ->icon('heroicon-o-bolt')
                ->color('warning')
                ->description("Gross discharge: {$this->record->detail->discharge} kWh & Regenerative Braking: {$this->record->detail->charge} kWh")
                ->chart($consumptionArray),
            Stat::make('Distance',Number::format($this->record->detail->distance??0).'km')
                ->icon('heroicon-o-map')
                ->color('success')
                ->chart($distancesArray),
        ];
    }
}
