<?php

namespace App\Filament\Resources\Obd2LogsResource\Pages;

use App\Filament\Imports\Obd2LogsImporter;
use App\Filament\Resources\Obd2LogsResource;
use App\Models\Obd2Logs;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListObd2Logs extends ListRecords
{
    protected static string $resource = Obd2LogsResource::class;

    protected function getHeaderActions(): array
    {
        return [
            //Actions\CreateAction::make(),
            Actions\Action::make("log_driving")
                ->label("Log Driving")
                ->action(function (){
                    $log = Obd2Logs::selectRaw('pid,MIN(value) AS value')
                        ->distinct()
                        ->groupBy('pid')
                        ->pluck('value','pid');
                    logger(json_encode($log->toArray()));
                    //Obd2Logs::truncate();
                }),
            Actions\ImportAction::make()
                ->importer(Obd2LogsImporter::class)
                ->csvDelimiter(";"),
        ];
    }
}
