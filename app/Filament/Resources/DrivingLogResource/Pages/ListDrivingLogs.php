<?php

namespace App\Filament\Resources\DrivingLogResource\Pages;

use App\Filament\Resources\DrivingLogResource;
use Filament\Actions;
use Filament\Pages\Concerns\ExposesTableToWidgets;
use Filament\Resources\Pages\ListRecords;

class ListDrivingLogs extends ListRecords
{
    use ExposesTableToWidgets;
    protected static string $resource = DrivingLogResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
    protected function getHeaderWidgets(): array
    {
        return [
            DrivingLogResource\Widgets\DrivingOverview::class,
        ];
    }
}
