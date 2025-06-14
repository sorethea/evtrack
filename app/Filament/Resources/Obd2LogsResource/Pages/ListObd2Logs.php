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
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Get;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use League\Csv\Reader;

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
                            ->options(EvLog::select(['id','date'])->orderBy('date','desc')->get()->pluck('date','id'))
                            ->default(fn()=>EvLog::max('id'))
                            ->searchable(['id','date'])
                            ->nullable(),
                        Select::make("log_type")
                            ->live()
                            ->label(trans('ev.log_types.name'))
                            ->options(trans("ev.log_types.options"))
                            ->default('driving')
                            ->nullable(),
                        Select::make("cycle_id")
                            ->reactive()
                            ->label(trans('ev.cycle'))
                            ->options(EvLog::select(['id','date'])->where('log_type','charging')->orderBy('date','desc')->get()->pluck('date','id'))
                            //->relationship('cycle','date')
                            ->hidden(fn(Get $get)=>$get("log_type")!="driving")
                            ->default(fn()=>EvLog::where("log_type","charging")->max('id'))
                            ->searchable(['id','date'])
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
                    if($evLog->date>=$maxEvLog){
                        $evLog->vehicle()->update([
                            'soc'=>$evLog->soc_actual,
                            'ac'=>$evLog->ac,
                            'ad'=>$evLog->ad,
                            'odo'=>$evLog->odo,

                        ]);
                    }

                    //Obd2Logs::truncate();

                }),
//            Actions\ImportAction::make()
//                ->importer(Obd2LogsImporter::class)
//                ->maxRows(100)
//                ->csvDelimiter(";"),
/*            Actions\Action::make('importObd')
                ->label('Import Obd2')
                ->form([
                    FileUpload::make('obd_file')
                        ->disk('local')
                        ->directory('obd2'),
                ])
                ->action(function (array $data){
                    $csv = Reader::createFromPath(Storage::path($data['obd_file']),'r');
                    $csv->setDelimiter(';');
                    foreach ($csv->getRecords() as $index=>$record){
                        if($index >=100) break;
                        Obd2Logs::where('pid',$record[1])->update([
                            'seconds' => $record[0],
                            'value' => $record[2],
                        ]);
                    }

                }),*/
        ];
    }
}
