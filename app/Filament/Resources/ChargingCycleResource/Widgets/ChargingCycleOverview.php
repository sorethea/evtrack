<?php

namespace App\Filament\Resources\ChargingCycleResource\Widgets;

use App\Models\ChargingCycle;
use Carbon\Carbon;
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
        $distancesArray = $this->record->children->pluck('distance')->toArray();
        $from_date = Carbon::parse($this->record->from_date)->format('d M, Y');
        $to_date = Carbon::parse($this->record->to_date)->format('d M, Y');

        return [
            Stat::make('Total distance',Number::format($this->record->distance??0).'km')
                ->icon('heroicon-o-map')
                ->color('success')
                ->description("From {$from_date} to {$to_date} ({$this->record->days} days)")
                ->chart($distancesArray),
        ];
    }
}
