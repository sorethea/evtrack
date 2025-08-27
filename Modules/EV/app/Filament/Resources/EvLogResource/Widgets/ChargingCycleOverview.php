<?php

namespace Modules\EV\Filament\Resources\EvLogResource\Widgets;


use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Illuminate\Database\Eloquent\Model;
use Modules\EV\Helpers\EvLog;

class ChargingCycleOverview extends BaseWidget
{
    public Model $record;
    protected function getStats(): array
    {
        return EvLog::getCycleOverview($this->record);
    }

    protected function getColumns(): int
    {
        return 4;
    }
}
