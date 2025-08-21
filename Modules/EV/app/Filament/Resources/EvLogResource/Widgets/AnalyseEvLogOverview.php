<?php

namespace Modules\EV\Filament\Resources\EvLogResource\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Database\Eloquent\Model;

class AnalyseEvLogOverview extends BaseWidget
{
    public Model $record;
    protected ?string $heading = 'Overview';
    protected function getStats(): array
    {
        return [
            //
        ];
    }
}
