<?php

namespace Modules\EV\Filament\Resources;

use App\Filament\Resources\ChargingCycleResource\Pages;
use App\Filament\Resources\ChargingCycleResource\RelationManagers;
use BackedEnum;
use Carbon\Carbon;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Number;
use Illuminate\Validation\Rules\Numeric;
use Modules\EV\Filament\Resources\ChargingCycleResource\Pages\ManageChargingCycles;
use Modules\EV\Filament\Resources\ChargingCycleResource\Pages\ViewChargingCycle;
use Modules\EV\Filament\Resources\EvLogResource\RelationManagers\LogsRelationManager;
use Modules\EV\Models\ChargingCycle;

class ChargingCycleResource extends Resource
{
    protected static ?string $model = ChargingCycle::class;

     protected static string | BackedEnum | null $navigationIcon= 'heroicon-o-bolt';

    public static function form(Schema $form): Schema
    {
        return $form
            ->schema([
                //
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ColumnGroup::make(__("ev.date"),[
                    Tables\Columns\TextColumn::make('cycle_date')
                        ->label(__("ev.from"))
                        ->date('d/m/y H:i')
                        ->searchable(),
                    Tables\Columns\TextColumn::make('end_date')
                        ->label(__("ev.to"))
                        ->date('d/m/y H:i')
                        ->searchable(),
                    Tables\Columns\TextColumn::make('days')
                        ->label(__("ev.day"))
                        ->getStateUsing(fn($record)=>number_format(Carbon::make($record->cycle_date)->diffInDays($record->end_date),0))
                ]),
                Tables\Columns\ColumnGroup::make(__("ev.soc")."(%)",[
                    Tables\Columns\TextColumn::make('root_soc')
                        ->label(__("ev.from"))
                        ->toggleable(true),
                    Tables\Columns\TextColumn::make('last_soc')
                        ->label(__("ev.to"))
                        ->toggleable(true),
                    Tables\Columns\TextColumn::make('soc_derivation')
                        ->label(__("ev.used"))
                        ->toggleable(true),
//                    Tables\Columns\TextColumn::make('soc_middle')
//                        ->numeric(1)
//                        ->label(__("ev.soc_middle"))
//                        ->toggleable(isToggledHiddenByDefault: true),
                    Tables\Columns\TextColumn::make('avg_discharge_consume')
                        ->numeric(1)
                        ->label(__("ev.consume"))
                        ->toggleable(isToggledHiddenByDefault: true),
                ]),
                Tables\Columns\ColumnGroup::make(__("ev.next_cycle"),[
                    Tables\Columns\TextColumn::make('next_soh')
                        ->numeric(1)
                        ->label(__("ev.soh")."(%)")
                        ->toggleable(true),
                    Tables\Columns\TextColumn::make('be_charge')
                        ->label(__("ev.charge"))
                        ->toggleable(true),
                    Tables\Columns\TextColumn::make('next_charge')
                        ->label(__("ev.charged"))
                        ->toggleable(true),
                ]),
//                Tables\Columns\ColumnGroup::make(trans('ev.accumulative').'(Ah)',[
//                    Tables\Columns\TextColumn::make('charge_amp')
//                        ->numeric(1)
//                        ->label(trans('ev.charge') )
//                        ->summarize(Tables\Columns\Summarizers\Sum::make()->label(trans('ev.charge'))),
//                    Tables\Columns\TextColumn::make('discharge_amp')
//                        ->numeric(1)
//                        ->label(trans('ev.discharge') )
//                        ->summarize(Tables\Columns\Summarizers\Sum::make()->label(trans('ev.discharge'))),
//                    Tables\Columns\TextColumn::make('a_consumption_amp')
//                        ->numeric(1)
//                        ->formatStateUsing(fn($state)=>($state>0)?Number::format($state,1):0)
//                        ->label(__('ev.consumption'))
//                        ->toggleable(isToggledHiddenByDefault: true),
//                    Tables\Columns\TextColumn::make('capacity_amp')
//                        ->label('Capacity')
//                        ->numeric(1),
//                ]),
                Tables\Columns\ColumnGroup::make(trans('ev.accumulative').'(kWh)',[
                    Tables\Columns\TextColumn::make('charge_from_regen')
                        ->numeric(1)
                        ->label(trans('ev.regen') )
                        ->summarize(Tables\Columns\Summarizers\Sum::make()->label(trans('ev.charge'))),
                    Tables\Columns\TextColumn::make('charge_from_charging')
                        ->numeric(1)
                        ->label(trans('ev.charge') )
                        ->summarize(Tables\Columns\Summarizers\Sum::make()->label(trans('ev.charge'))),
                    Tables\Columns\TextColumn::make('percentage_charge')
                        ->numeric(1)
                        ->formatStateUsing(fn($state)=>($state>0)?Number::format($state,1):0)
                        ->label(__('ev.percentage_charge'))
                        ->toggleable(isToggledHiddenByDefault: true),
                    Tables\Columns\TextColumn::make('discharge')
                        ->numeric(1)
                        ->label(trans('ev.discharge') )
                        ->summarize(Tables\Columns\Summarizers\Sum::make()->label(trans('ev.discharge'))),

                    Tables\Columns\TextColumn::make('a_consumption')
                        ->numeric(1)
                        ->formatStateUsing(fn($state)=>($state>0)?Number::format($state,1):0)
                        ->label(__('ev.consumption'))
                        ->toggleable(isToggledHiddenByDefault: true),

                    Tables\Columns\TextColumn::make('used_energy')
                        ->numeric(1)
                        ->formatStateUsing(fn($state)=>($state>0)?Number::format($state,1):0)
                        ->label(__('ev.used'))
                        ->toggleable(isToggledHiddenByDefault: false),
                    Tables\Columns\TextColumn::make('capacity')
                        ->label(trans('ev.capacity'))
                        ->numeric(1),
                ]),

                Tables\Columns\TextColumn::make('range')
                    ->label(__('ev.range'))
                    ->numeric(1),
                Tables\Columns\TextColumn::make('distance')
                    ->label(__('ev.distance'))
                    ->numeric(1)
                    ->summarize(Tables\Columns\Summarizers\Sum::make()),

            ])
            ->defaultSort('cycle_date','DESC')
            ->filters([
                //
            ])
            ->actions([
                EditAction::make()->hidden(),
                DeleteAction::make()->hidden(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()->hidden(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageChargingCycles::route('/'),
            'view' =>  ViewChargingCycle::route('/{record}'),
        ];
    }
    public static function getRelations(): array
    {
        return [
          LogsRelationManager::class,
        ];
    }
}
