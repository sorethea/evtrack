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
        $socArray = $this->record->cycle->logs->pluck('soc')->toArray();
        return [
            Stat::make('Current SoC',Number::format($this->record->detail->soc??0).'%')
                ->icon('heroicon-o-battery-50')
                ->color('danger')
                ->description("Battery from {$this->record->cycle->root_soc}% to {$this->record->detail->soc}%")
                ->chart($socArray),
        ];
    }
}
