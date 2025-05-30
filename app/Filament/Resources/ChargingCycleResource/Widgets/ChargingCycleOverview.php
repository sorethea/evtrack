<?php

namespace App\Filament\Resources\ChargingCycleResource\Widgets;

use App\Models\ChargingCycle;
use Filament\Forms\Get;
use Filament\Resources\Pages\Concerns\InteractsWithRecord;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Database\Eloquent\Model;

class ChargingCycleOverview extends BaseWidget
{
    //use InteractsWithRecord;

    public int $record_id;


    protected function getStats(): array
    {

        return [
            Stat::make('Distance',$this->record_id??0)
        ];
    }
}
