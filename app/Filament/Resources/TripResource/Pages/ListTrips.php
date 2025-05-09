<?php

namespace App\Filament\Resources\TripResource\Pages;

use App\Filament\Resources\ChargeResource\Widgets\ChargeCost;
use App\Filament\Resources\TripResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListTrips extends ListRecords
{
    protected static string $resource = TripResource::class;

    protected function getHeaderWidgets(): array
    {
        return [
          ChargeCost::class,
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
