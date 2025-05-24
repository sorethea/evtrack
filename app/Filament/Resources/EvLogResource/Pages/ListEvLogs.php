<?php

namespace App\Filament\Resources\EvLogResource\Pages;

use App\Filament\Resources\EvLogResource;
use Filament\Actions;
use Filament\Pages\Concerns\ExposesTableToWidgets;
use Filament\Resources\Pages\ListRecords;
use Filament\Tables\Columns\Concerns\InteractsWithTableQuery;
use Illuminate\Database\Eloquent\Builder;

class ListEvLogs extends ListRecords
{
    use ExposesTableToWidgets;

    protected static string $resource = EvLogResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            EvLogResource\Widgets\EvLogOverview::class,
        ];
    }
}
