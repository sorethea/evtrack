<?php

namespace App\Helpers;

use Filament\Forms\Components\Fieldset;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Get;

class EvLog
{
    public static function getItemValue(\App\Models\EvLog $evLog, int $item_id):float {
        return $evLog->items->where('item_id',$item_id)->value('value')??0;
    }
    public static function getParentItemValue(\App\Models\EvLog $evLog, int $item_id):float {
        return $evLog->parent->items->where('item_id',$item_id)->value('value')??0;
    }
    public static function getCycleItemValue(\App\Models\EvLog $evLog, int $item_id):float {
        return $evLog->cycle->items->where('item_id',$item_id)->value('value')??0;
    }
    public static function getDistance(\App\Models\EvLog $evLog):float
    {
        return (self::getItemValue($evLog,1) - self::getParentItemValue($evLog,1))??0;
    }

    public static function obdImportForm():array
    {
        return [
            Fieldset::make()->schema([
//                        TextInput::make('date')
//                            ->label(trans('ev.date'))
//                            ->required(),
                Select::make("log_type")
                    ->live()
                    ->label(trans('ev.log_types.name'))
                    ->options(trans("ev.log_types.options"))
                    ->default('driving')
                    ->nullable(),
                Select::make('parent_id')
                    ->label(trans('ev.parent'))
                    ->options(\App\Models\EvLog::select(['id','date'])->orderBy('date','desc')->get()->pluck('date','id'))
                    ->default(fn()=>EvLog::max('id'))
                    ->searchable(['id','date'])
                    ->nullable(),
                Select::make("cycle_id")
                    ->reactive()
                    ->label(trans('ev.cycle'))
                    ->options(\App\Models\EvLog::select(['id','date'])->where('log_type','charging')->orderBy('date','desc')->get()->pluck('date','id'))
                    //->relationship('cycle','date')
                    ->hidden(fn(Get $get)=>$get("log_type")=="charge")
                    ->default(fn()=>EvLog::where("log_type","charging")->max('id'))
                    ->searchable(['id','date'])
                    ->nullable(),
                Select::make("charge_type")
                    ->label(trans('ev.charge_types.name'))
                    ->options(trans("ev.charge_types.options"))
                    ->hidden(fn(Get $get)=>$get("log_type")!="charging")
                    ->nullable(),
                FileUpload::make('obd_file')
                    ->preserveFilenames()
                    ->disk('local')
                    ->directory('obd2'),
            ])->columns(2),

        ];
    }
}
