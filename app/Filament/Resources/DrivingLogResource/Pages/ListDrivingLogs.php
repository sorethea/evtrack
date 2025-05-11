<?php

namespace App\Filament\Resources\DrivingLogResource\Pages;

use App\Filament\Imports\DrivingLogImporter;
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
            Actions\ImportAction::make()
                ->importer(DrivingLogImporter::class)
                ->csvDelimiter(";")
                ->chunkSize(22),
        ];
    }
    protected function getHeaderWidgets(): array
    {
        return [
            DrivingLogResource\Widgets\DrivingOverview::class,
        ];
    }
}
