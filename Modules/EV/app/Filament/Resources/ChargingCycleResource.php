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
                Tables\Columns\TextColumn::make('cycle_date')
                    ->date('d/m/y H:i')
                    ->searchable(),
                Tables\Columns\TextColumn::make('end_date')
                    ->date('d/m/y H:i')
                    ->searchable(),
                Tables\Columns\TextColumn::make('cycle_soc')
                    ->label('From SOC(%)')
                    ->toggleable(true),
                Tables\Columns\TextColumn::make('end_soc')
                    ->label('To SOC (%)')
                    ->toggleable(true),
//                Tables\Columns\TextColumn::make('charge')
//                    ->label('Charge (%)')
//                    ->toggleable(true),
//                Tables\Columns\TextColumn::make('discharge')
//                    ->label('Discharge (%)')
//                    ->toggleable(true),
//                Tables\Columns\TextColumn::make('a_regen')
//                    ->label('Regen(kWh)')
//                    ->toggleable()
//                    ->toggledHiddenByDefault(),
//                Tables\Columns\TextColumn::make('a_charge')
//                    ->label('Acc Charge')
//                    ->toggleable()
//                    ->toggledHiddenByDefault(),
//                Tables\Columns\TextColumn::make('a_discharge')
//                    ->label('Acc Discharge')
//                    ->toggleable()
//                    ->toggledHiddenByDefault(),
//                Tables\Columns\TextColumn::make('gap_zero')
//                    ->label('Gap Zero')
//                    ->toggleable()
//                    ->toggledHiddenByDefault(),
//                Tables\Columns\TextColumn::make('consumption')
//                    ->label('kWh/100km'),
//                Tables\Columns\TextColumn::make('distance')
//                    ->label('Distance (km)')
//                    ->summarize(Tables\Columns\Summarizers\Sum::make()),
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
            //'view' =>  \Modules\EV\Filament\Resources\ChargingCycleResource\Pages\ViewChargingCycle::route('/{record}'),
        ];
    }
}
