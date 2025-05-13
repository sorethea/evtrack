<?php

namespace App\Filament\Resources;

use App\Filament\Resources\EvLogResource\Pages;
use App\Filament\Resources\EvLogResource\RelationManagers;
use App\Models\EvLog;
use Carbon\Carbon;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Number;

class EvLogResource extends Resource
{
    protected static ?string $model = EvLog::class;

    protected static ?string $navigationIcon = 'heroicon-o-calendar';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make([
                    Forms\Components\DatePicker::make("date")
                        ->label(trans('ev.date'))
                        ->default(now()->format('Y-m-d'))
                        ->required(),
                    Forms\Components\TimePicker::make("seconds")
                        ->label(trans('ev.time'))
                        ->nullable(),
                    Forms\Components\Select::make("parent_id")
                        ->live()
                        ->label(trans('ev.parent'))
                        ->relationship('parent','date')
                        ->getOptionLabelFromRecordUsing(fn (Model $record) =>"{$record->id}-". Carbon::parse($record->date)->format('dmY'))
                        ->default(fn()=>EvLog::max('id'))
                        ->searchable(['id','date'])
                        ->nullable(),
                    Forms\Components\Select::make("log_type")
                        ->live()
                        ->label(trans('ev.log_types.name'))
                        ->options(trans("ev.log_types.options"))
                        ->default('driving')
                        ->nullable(),
                    Forms\Components\TextInput::make("odo")
                        ->label(trans('ev.odo'))
                        ->required(),
                    Forms\Components\TextInput::make("soc")
//                        ->live(onBlur: true)
//                        ->afterStateUpdated(fn(Set $set,?float $state,Get $get)=>$set('capacity',round(EvLog::find($get('parent_id'))->soc-$state,1)))
                        ->label(trans('ev.soc'))
                        ->required(),
                    Forms\Components\Fieldset::make()->label(trans('ev.obd2'))
                    ->schema([
                        Forms\Components\TextInput::make("ac")
                            ->label(trans('ev.charge'))
                            ->nullable(),
                        Forms\Components\TextInput::make("ad")
                            ->label(trans('ev.discharge'))
                            ->nullable(),
                        Forms\Components\TextInput::make("min_cell_voltage")
                            ->label(trans('ev.min_cell_voltage'))
                            ->nullable(),
                        Forms\Components\TextInput::make("max_cell_voltage")
                            ->label(trans('ev.max_cell_voltage'))
                            ->nullable(),
                        Forms\Components\TextInput::make("voltage")
                            ->label(trans('ev.voltage'))
                            ->nullable(),
                    ]),

                    Forms\Components\Select::make("charge_type")
                        ->label(trans('ev.charge_types.name'))
                        ->options(trans("ev.charge_types.options"))
                        ->hidden(fn(Get $get)=>$get("log_type")!="charging")
                        ->nullable(),
//                    Forms\Components\TextInput::make("capacity")
//                        ->reactive()
//                        ->label(trans('ev.capacity'))
//                        ->helperText('Negative represent charging capacity and positive represent discharging capacity. Unit %')
//                        ->nullable(),

                    Forms\Components\TextInput::make("remark")
                        ->label(trans('ev.remark'))
                        ->nullable(),

                ])->columns(2)
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make("date")
                    ->date('d M, Y')
                    ->searchable(),
                Tables\Columns\TextColumn::make("log_type")
                    ->label(trans('ev.type'))
                    ->formatStateUsing(fn(string $state):string =>trans("ev.log_types.options.{$state}"))
                    ->searchable(),
                Tables\Columns\TextColumn::make('distance')
                    ->label(trans('ev.distance'))
                    ->default(fn ($record)=>Number::format(!empty($record?->parent?->odo)?$record->odo - $record?->parent?->odo:$record->odo,0)."km"),
                Tables\Columns\TextColumn::make('soc')
                    ->label(trans('ev.soc'))
                    ->formatStateUsing(fn($state)=>$state."%")
                    ->searchable(),
                Tables\Columns\TextColumn::make('power')
                    ->label(trans('ev.power'))
                    ->default(function(Model $record){
                        $capacity = $record->vehicle->capacity/100 * ($record?->parent?->soc? $record->parent->soc - $record->soc:0);
                        return Number::format($capacity,1)."kWh";
                    }),
                    //->formatStateUsing(fn(float $state, Model $record) =>Number::format($state * $record->vehicle->capacity/100,1)."kWh"),

                Tables\Columns\TextColumn::make('consumption')
                    ->label(trans('ev.consumption'))
                    ->default(function(Model $record){
                        $distance = !empty($record?->parent?->odo)?$record->odo - $record?->parent?->odo:$record->odo;
                        $capacity = $record->vehicle->capacity/100 * ($record?->parent?->soc? $record->parent->soc - $record->soc:0);
                        return $distance>0 ? Number::format($capacity/$distance * 100,0)."kWh/100km":"";
                    }),
                Tables\Columns\TextColumn::make('range')
                    ->label(trans('ev.range'))
                    ->default(function(Model $record){
                        $distance = !empty($record?->parent?->odo)?$record->odo - $record?->parent?->odo:$record->odo;
                        $capacity = $record->vehicle->capacity/100 * ($record?->parent?->soc? $record->parent->soc - $record->soc:0);
                        return $capacity>0? Number::format($distance/$capacity * 100,0)."km":"";
                    }),
            ])
            ->filters([
                //
            ])
            ->defaultSort(fn(Builder $query)=>$query->orderBy('date','desc')->orderBy('id','desc'))
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListEvLogs::route('/'),
            'create' => Pages\CreateEvLog::route('/create'),
            'edit' => Pages\EditEvLog::route('/{record}/edit'),
        ];
    }
}
