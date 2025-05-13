<?php

namespace App\Filament\Resources\Obd2LogsResource\Pages;

use App\Filament\Imports\Obd2LogsImporter;
use App\Filament\Resources\Obd2LogsResource;
use App\Models\DrivingLog;
use App\Models\EvLog;
use App\Models\Obd2Logs;
use Filament\Actions;
use Filament\Forms\Components\Fieldset;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
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
                ->form([
                    Fieldset::make([
                        Select::make('parent_id')
                            ->label(trans('ev.parent'))
                            ->options(EvLog::orderBy('id','desc')->select(['id','date'])->get()->pluck('date','id'))
                            ->searchable(['id','date']),
                    ])->columns(2)

                ])
                ->action(function (){
//                    $log = Obd2Logs::selectRaw('pid,MIN(value) AS value')
//                        ->distinct()
//                        ->where('pid','like','[BMS]%')
//                        ->orWhere('pid','like','[VCU] Odometer%')
//                        ->groupBy('pid')
//                        ->pluck('value','pid')->toArray();
//                    $drivingLogLastest = DrivingLog::orderBy('date','desc')->first();
//                    $drivingLog = new DrivingLog();
//                    $drivingLog->date = now()->format('Y-m-d');
//                    $drivingLog->type = "driving";
//                    $drivingLog->soc_from = $drivingLogLastest->soc_to;
//                    $drivingLog->soc_to = $log[config("ev.obd2logs.soc_to")];
//                    $drivingLog->ac = $log[config("ev.obd2logs.ac")];
//                    $drivingLog->ad = $log[config("ev.obd2logs.ad")];
//                    $drivingLog->odo = $log[config("ev.obd2logs.odo")];
//                    $drivingLog->voltage = $log[config("ev.obd2logs.voltage")];
//                    $drivingLog->save();
//                    Obd2Logs::truncate();
                }),
            Actions\ImportAction::make()
                ->importer(Obd2LogsImporter::class)
                ->csvDelimiter(";"),
        ];
    }
}
