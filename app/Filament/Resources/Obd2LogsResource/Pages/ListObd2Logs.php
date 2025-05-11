<?php

namespace App\Filament\Resources\Obd2LogsResource\Pages;

use App\Filament\Imports\Obd2LogsImporter;
use App\Filament\Resources\Obd2LogsResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListObd2Logs extends ListRecords
{
    protected static string $resource = Obd2LogsResource::class;

    protected function getHeaderActions(): array
    {
        return [
            //Actions\CreateAction::make(),
            Actions\ImportAction::make()
                ->importer(Obd2LogsImporter::class)
                ->csvDelimiter(";"),
        ];
    }
}
