<?php

namespace App\Filament\Resources\Obd2LogsResource\Pages;

use App\Filament\Imports\Obd2LogsImporter;
use App\Filament\Resources\Obd2LogsResource;
use App\Models\DrivingLog;
use App\Models\EvLog;
use App\Models\Obd2Logs;
use Filament\Actions;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Fieldset;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Get;
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
                    Fieldset::make()->schema([
                        DateTimePicker::make('date')
                            ->label(trans('ev.date'))
                            ->format('Y-m-d h:i')
                            ->required(),
                        Select::make('parent_id')
                            ->label(trans('ev.parent'))
                            ->options(EvLog::select(['id','date'])->orderBy('id','desc')->get()->pluck('date','id'))
                            ->searchable(['id','date'])
                            ->required(),
                        Select::make("log_type")
                            ->live()
                            ->label(trans('ev.log_types.name'))
                            ->options(trans("ev.log_types.options"))
                            ->default('driving')
                            ->required(),
                        Select::make("charge_type")
                            ->label(trans('ev.charge_types.name'))
                            ->options(trans("ev.charge_types.options"))
                            ->hidden(fn(Get $get)=>$get("log_type")!="charging")
                            ->nullable(),
                    ])->columns(2)

                ])
                ->action(function (array $data){
                    $obd2Logs = config('ev.obd2logs');
                    $log = Obd2Logs::selectRaw('pid,MIN(value) AS value')
                        ->distinct()
                        ->whereIn('pid', array_keys($obd2Logs))
                        ->groupBy('pid')
                        ->pluck('value','pid')->toArray();
                    $parent =EvLog::find($data["parent_id"]);
                    $evLog = new EvLog();
                    $evLog->date=$data["date"];
                    $evLog->parent_id=$parent;
                    $evLog->log_type=$data["log_type"];
                    $evLog->charge_type=$data["charge_type"]??"";
                    foreach ($obd2Logs as $key=>$value){
                        $evLog->$value=$log[$key];
                    }
                    $evLog->distance = round($evLog->odo - $parent->odo,1);

                    $evLog->save();

                }),
            Actions\ImportAction::make()
                ->importer(Obd2LogsImporter::class)
                ->csvDelimiter(";"),
        ];
    }
}
