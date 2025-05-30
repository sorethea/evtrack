<?php

namespace App\Filament\Resources\ChargingCycleResource\Widgets;

use App\Models\ChargingCycle;
use Filament\Forms\Get;
use Filament\Resources\Pages\Concerns\InteractsWithRecord;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Number;

class ChargingCycleOverview extends BaseWidget
{
    //use InteractsWithRecord;

    public Model $record;


    protected function getStats(): array
    {

        return [
            Stat::make('Distance',Number::format($this->record->distance??0).'km'),
        ];
    }
}
