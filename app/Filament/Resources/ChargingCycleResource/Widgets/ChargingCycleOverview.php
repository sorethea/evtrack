<?php

namespace App\Filament\Resources\ChargingCycleResource\Widgets;

use Filament\Forms\Get;
use Filament\Resources\Pages\Concerns\InteractsWithRecord;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Database\Eloquent\Model;

class ChargingCycleOverview extends BaseWidget
{
    use InteractsWithRecord;

//    public function getRecord(): Model
//    {
//        return $this->record;
//    }

    protected function getStats(): array
    {
        dd($this->record);
        return [
            Stat::make('Distance','100km')
        ];
    }
}
