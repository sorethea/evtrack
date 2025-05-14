<?php

namespace App\Filament\Resources\EvLogResource\Pages;

use App\Filament\Resources\EvLogResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Tables\Columns\Concerns\InteractsWithTableQuery;
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
}
