<?php

namespace App\Filament\Resources\EvLogResource\Pages;

use App\Filament\Resources\EvLogResource;
use App\Models\EvLog;
use App\Models\EvLogItem;
use App\Models\Obd2Logs;
use App\Models\ObdItem;
use Filament\Actions;
use Filament\Forms\Components\Fieldset;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Get;
use Filament\Pages\Concerns\ExposesTableToWidgets;
use Filament\Resources\Pages\ListRecords;
use Filament\Tables\Columns\Concerns\InteractsWithTableQuery;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Storage;
use League\Csv\Reader;

class ListEvLogs extends ListRecords
{
    use ExposesTableToWidgets;

    protected static string $resource = EvLogResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
            Actions\Action::make('obdImport')
                ->label('Obd Import')
                ->form([
                    Fieldset::make()->schema([
                        TextInput::make('date')
                            ->label(trans('ev.date'))
                            ->required(),
                        Select::make("log_type")
                        ->live()
                        ->label(trans('ev.log_types.name'))
                        ->options(trans("ev.log_types.options"))
                        ->default('driving')
                        ->nullable(),
                        Select::make('parent_id')
                            ->label(trans('ev.parent'))
                            ->options(EvLog::select(['id','date'])->orderBy('date','desc')->get()->pluck('date','id'))
                            ->default(fn()=>EvLog::max('id'))
                            ->searchable(['id','date'])
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
                        FileUpload::make('obd_file')
                            ->disk('local')
                            ->directory('obd2'),
                    ])->columns(2),

                ])
                ->action(function (array $data){

                    $csv = Reader::createFromPath(Storage::path($data['obd_file']),'r');
                    $csv->setDelimiter(';');
                    unset($data['obd_file']);
                    $evLog = EvLog::create($data);
                    foreach ($csv->getRecords() as $index=>$record){
                        logger($record);
                        if($index >=200) break;
                        $item = ObdItem::where('pid',$record[1])->first();
                        if(!empty($item) && $item->id){
                            $evLog->items()->updateOrCreate([
                                'item_id'=>$item->id,
                                'value'=>$record[2],
                            ]);
                        }
                    }

                }),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            //EvLogResource\Widgets\EvLogOverview::class,
            EvLogResource\Widgets\ChargingCycleOverview::make(),
        ];
    }
}
