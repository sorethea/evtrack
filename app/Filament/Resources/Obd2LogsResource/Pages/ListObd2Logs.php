<?php

namespace App\Filament\Resources\Obd2LogsResource\Pages;

use App\Filament\Imports\Obd2LogsImporter;
use App\Filament\Resources\Obd2LogsResource;
use App\Models\DrivingLog;
use App\Models\EvLog;
use App\Models\Obd2Logs;
use Carbon\Carbon;
use Filament\Actions;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Fieldset;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Get;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Model;

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
                        TextInput::make('date')
                            ->label(trans('ev.date'))
                            ->required(),
                        Select::make('parent_id')
                            ->label(trans('ev.parent'))
                            ->options(EvLog::select(['id','date'])->orderBy('id','desc')->get()->pluck('date','id'))
                            ->searchable(['id','date'])
                            ->nullable(),
                        Select::make("log_type")
                            ->live()
                            ->label(trans('ev.log_types.name'))
                            ->options(trans("ev.log_types.options"))
                            ->default('driving')
                            ->nullable(),
                        Select::make("charge_type")
                            ->label(trans('ev.charge_types.name'))
                            ->options(trans("ev.charge_types.options"))
                            ->hidden(fn(Get $get)=>$get("log_type")!="charging")
                            ->nullable(),
                    ])->columns(2)

                ])
                ->action(function ( array $data){
                    $obd2Logs = config('ev.obd2logs');
                    $log = Obd2Logs::selectRaw('pid,MIN(value) AS value')
                        ->distinct()
                        ->whereIn('pid', array_keys($obd2Logs))
                        ->groupBy('pid')
                        ->pluck('value','pid')->toArray();
                    foreach ($obd2Logs as $key=>$value){
                        $data[$value]=$log[$key]??null;
                    }
                    $maxEvLog = EvLog::max('date');
                    $evLog = EvLog::create($data);
                    if(Carbon::parse($maxEvLog)->lessThanOrEqualTo($evLog->date))
                        $evLog->vehicle->save(['soc'=>$evLog->soc_actual,'odo'=>$evLog->odo]);
                    Obd2Logs::truncate();

                }),
            Actions\ImportAction::make()
                ->importer(Obd2LogsImporter::class)
                ->csvDelimiter(";"),
        ];
    }
}
