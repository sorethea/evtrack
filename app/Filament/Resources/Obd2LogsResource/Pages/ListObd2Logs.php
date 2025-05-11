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
                    $log = Obd2Logs::selectRaw("SELECT
                          t1.pid,
                          t1.value
                        FROM obd2_logs t1
                        INNER JOIN (
                          SELECT
                            pid,
                            MIN(seconds) AS min_seconds
                          FROM obd2_logs
                          WHERE pid LIKE '[BMS]%' OR pid LIKE '[VCU] Odometer%' -- Filter for BMS parameters
                          GROUP BY pid
                        ) t2 ON t1.pid = t2.pid AND t1.seconds = t2.min_seconds
                        ORDER BY t1.seconds DESC");
                    logger(json_encode($log->toArray()));
                    //Obd2Logs::truncate();
                }),
            Actions\ImportAction::make()
                ->importer(Obd2LogsImporter::class)
                ->csvDelimiter(";"),
        ];
    }
}
