<?php

namespace App\Filament\Resources;

use App\Filament\Resources\EvLogResource\Pages;
use App\Filament\Resources\EvLogResource\RelationManagers;
use App\Models\EvLog;
use Carbon\Carbon;
use Doctrine\DBAL\Query\QueryBuilder;
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
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Number;
use Symfony\Component\Mime\Encoder\QpContentEncoder;

class EvLogResource extends Resource
{
    protected static ?string $model = EvLog::class;

    protected static ?string $navigationIcon = 'heroicon-o-calendar';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make([
                    Forms\Components\DateTimePicker::make("date")
                        ->label(trans('ev.date'))
                        ->default(now()->format('Y-m-d h i'))
                        ->required(),
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
                    Forms\Components\Select::make("charge_type")
                        ->label(trans('ev.charge_types.name'))
                        ->options(trans("ev.charge_types.options"))
                        ->hidden(fn(Get $get)=>$get("log_type")!="charging")
                        ->nullable(),
                    Forms\Components\Fieldset::make()->label(trans('ev.obd2'))
                    ->schema([
                        Forms\Components\TextInput::make("ac")
                            ->label(trans('ev.charge'))
                            ->nullable(),
                        Forms\Components\TextInput::make("ad")
                            ->label(trans('ev.discharge'))
                            ->nullable(),
                        Forms\Components\TextInput::make("highest_temp_cell")
                            ->label(trans('ev.highest_temp_cell'))
                            ->nullable(),
                        Forms\Components\TextInput::make("lowest_temp_cell")
                            ->label(trans('ev.lowest_temp_cell'))
                            ->nullable(),
                        Forms\Components\TextInput::make("highest_volt_cell")
                            ->label(trans('ev.highest_volt_cell'))
                            ->nullable(),
                        Forms\Components\TextInput::make("lowest_volt_cell")
                            ->label(trans('ev.lowest_volt_cell'))
                            ->nullable(),
                        Forms\Components\TextInput::make("voltage")
                            ->label(trans('ev.voltage'))
                            ->nullable(),
                    ]),
                    Forms\Components\Textarea::make("remark")
                        ->label(trans('ev.remark'))
                        ->columnSpan(2)
                        ->nullable(),

                ])->columns(2)
            ]);
    }
    public static function getEloquentQuery(): Builder
    {
//        return parent::getEloquentQuery()->selectRaw("
//        ROUND(odo - COALESCE(parent.odo,0),0) AS trip_distance,
//        CASE
//                WHEN parent.soc IS NOT NULL AND soc > parent.soc
//                THEN soc - parent.soc
//                ELSE 0
//            END as charge,
//            CASE
//                WHEN parent.soc IS NOT NULL AND parent.soc > soc
//                THEN parent.soc - soc
//                ELSE 0
//            END as discharge
//        ")
//            ->leftJoin('ev_logs as parent','ev_logs.parent_id','=','parent.id');
            //->joinRelationship('parent');
        return parent::getEloquentQuery()->newQuery()
            ->selectRaw("
        ROUND(ev_logs.odo - COALESCE(parent.odo,0),0) AS trip_distance,
        CASE
                WHEN parent.soc IS NOT NULL AND ev_logs.soc > parent.soc
                THEN ev_logs.soc - parent.soc
                ELSE 0
            END as charge,
            CASE
                WHEN parent.soc IS NOT NULL AND parent.soc > ev_logs.soc
                THEN parent.soc - ev_logs.soc
                ELSE 0
            END as discharge
        ")
            ->leftJoin('ev_logs as parent','ev_logs.parent_id','=','parent.id');
    }


    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make("date")
                    ->date('d M, Y h i')
                    ->searchable(),
                Tables\Columns\TextColumn::make("log_type")
                    ->label(trans('ev.type'))
                    ->formatStateUsing(fn(string $state):string =>trans("ev.log_types.options.{$state}"))
                    ->searchable(),
                Tables\Columns\TextColumn::make('soc')
                    ->label(trans('ev.soc'))
                    ->formatStateUsing(fn($state)=>$state."%")
                    ->searchable(),
                Tables\Columns\TextColumn::make('charge')
                    ->label(trans('ev.charge'))
                    ->formatStateUsing(fn($state)=>$state."%")
                    ->summarize(Tables\Columns\Summarizers\Sum::make()),
                Tables\Columns\TextColumn::make('discharge')
                    ->label(trans('ev.discharge'))
                    ->formatStateUsing(fn($state)=>$state."%")
                    ->summarize(Tables\Columns\Summarizers\Sum::make()),
                Tables\Columns\TextColumn::make('trip_distance')
                    ->label(trans('ev.distance'))
                    ->formatStateUsing(fn($state)=>$state."km")
                    ->summarize(Tables\Columns\Summarizers\Sum::make()),


//                Tables\Columns\TextColumn::make('consumption')
//                    ->label(trans('ev.consumption'))
//                    ->default(function(Model $record){
//                        $distance = !empty($record?->parent?->odo)?$record->odo - $record?->parent?->odo:$record->odo;
//                        $capacity = $record->vehicle->capacity/100 * ($record?->parent?->soc? $record->parent->soc - $record->soc:0);
//                        return $distance>0 ? Number::format($capacity/$distance * 100,0)."kWh/100km":"";
//                    }),
//                Tables\Columns\TextColumn::make('range')
//                    ->label(trans('ev.range'))
//                    ->default(function(Model $record){
//                        $distance = !empty($record?->parent?->odo)?$record->odo - $record?->parent?->odo:$record->odo;
//                        $capacity = $record->vehicle->capacity/100 * ($record?->parent?->soc? $record->parent->soc - $record->soc:0);
//                        return $capacity>0? Number::format($distance/$capacity * 100,0)."km":"";
//                    }),
            ])
            ->filters([
                Tables\Filters\QueryBuilder::make()
                    ->constraints([
                       Tables\Filters\QueryBuilder\Constraints\DateConstraint::make('date'),
                    ]),
                Tables\Filters\SelectFilter::make('log_type')
                    ->label(trans('ev.log_types.name'))
                    ->options(trans('ev.log_types.options')),
                Tables\Filters\SelectFilter::make('charge_type')
                    ->label(trans('ev.charge_types.name'))
                    ->options(trans('ev.charge_types.options')),

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
