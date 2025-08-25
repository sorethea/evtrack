<?php

namespace Modules\EV\Filament\Resources\ChargingCycleResource\Widgets;

use Carbon\Carbon;
use Filament\Support\Colors\Color;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Number;
use Modules\EV\Helpers\EvLog;

class ChargingCycleOverview extends BaseWidget
{
    //use InteractsWithRecord;

    public Model $record;


    protected function getStats(): array
    {
        //$log = $this->record;
        return EvLog::getCycleOverview($this->record->latestLog);
    }

    protected function getColumns(): int
    {
        return 4;
    }
}
