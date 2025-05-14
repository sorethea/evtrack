<?php

namespace App\Filament\Resources\EvLogResource\Pages;

use App\Filament\Resources\EvLogResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;

class ListEvLogs extends ListRecords
{
    protected static string $resource = EvLogResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
    protected function getTableQuery(): ?Builder
    {
        return parent::getTableQuery()
            ->selectRaw("
        ROUND(odo - COALESCE(parent.odo,0),0) AS trip_distance,
        CASE
                WHEN parent.soc IS NOT NULL AND soc > parent.soc
                THEN soc - parent.soc
                ELSE 0
            END as charge,
            CASE
                WHEN parent.soc IS NOT NULL AND parent.soc > soc
                THEN parent.soc - soc
                ELSE 0
            END as discharge
        ")
            ->leftJoin('ev_logs as parent','ev_logs.parent_id','=','parent.id');
            ->joinRelationship('parent');
    }
}
