<?php

namespace Modules\EV\Filament\Resources;

use App\Filament\Resources\ChargingCycleResource\Pages;
use App\Filament\Resources\ChargingCycleResource\RelationManagers;
use Carbon\Carbon;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Number;
use Modules\EV\Models\ChargingCycle;

class ChargingCycleResource extends Resource
{
    protected static ?string $model = ChargingCycle::class;

    protected static ?string $navigationIcon = 'heroicon-o-bolt';

    public static function form(Form $form): Form
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
                ]),
                Tables\Columns\ColumnGroup::make(__("ev.soc")."(%)",[
                    Tables\Columns\TextColumn::make('root_soc')
                        ->label(__("ev.from"))
                        ->toggleable(true),
                    Tables\Columns\TextColumn::make('last_soc')
                        ->label(__("ev.to"))
                        ->toggleable(true),
                    Tables\Columns\TextColumn::make('soc_derivation')
                        ->label(__("ev.soc_derivation"))
                        ->toggleable(true),
                    Tables\Columns\TextColumn::make('soc_middle')
                        ->numeric(1)
                        ->label(__("ev.soc_middle"))
                        ->toggleable(true),
                    Tables\Columns\TextColumn::make('consumption')
                        ->numeric(1)
                        ->label(__("ev.consumption"))
                        ->toggleable(true),
                ]),
                Tables\Columns\ColumnGroup::make(trans('ev.accumulative').'(kWh)',[
                    Tables\Columns\TextColumn::make('charge')
                        ->numeric(1)
                        ->label(trans('ev.charge') )
                        ->summarize(Tables\Columns\Summarizers\Sum::make()->label(trans('ev.charge'))),
                    Tables\Columns\TextColumn::make('discharge')
                        ->numeric(1)
                        ->label(trans('ev.discharge') )
                        ->summarize(Tables\Columns\Summarizers\Sum::make()->label(trans('ev.discharge'))),
                    Tables\Columns\TextColumn::make('a_consumption')
                        ->numeric(1)
                        ->formatStateUsing(fn($state)=>($state>0)?Number::format($state,1):0)
                        ->label(__('ev.consumption'))
                        ->toggleable(),
                ]),
                Tables\Columns\TextColumn::make('capacity')
                    ->label('Capacity (kWh)')
                    ->numeric(1)
                    ->summarize(Tables\Columns\Summarizers\Sum::make()),
                Tables\Columns\TextColumn::make('distance')
                    ->label('Distance (km)')
                    ->numeric(1)
                    ->summarize(Tables\Columns\Summarizers\Sum::make()),

            ])
            ->defaultSort('cycle_date','DESC')
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make()->hidden(),
                Tables\Actions\DeleteAction::make()->hidden(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()->hidden(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => \Modules\EV\Filament\Resources\ChargingCycleResource\Pages\ManageChargingCycles::route('/'),
            'view' =>  \Modules\EV\Filament\Resources\ChargingCycleResource\Pages\ViewChargingCycle::route('/{record}'),
        ];
    }
}
