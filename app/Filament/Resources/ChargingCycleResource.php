<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ChargingCycleResource\Pages;
use App\Filament\Resources\ChargingCycleResource\RelationManagers;
use App\Models\ChargingCycle;
use Carbon\Carbon;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Number;

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
                Tables\Columns\TextColumn::make('from_date')
                    ->date('d/m/y H:i')
                    ->searchable(),
                Tables\Columns\TextColumn::make('to_date')
                    ->date('d/m/y H:i')
                    ->searchable(),
                Tables\Columns\TextColumn::make('days')
                    ->getStateUsing(fn($record)=>Number::format(Carbon::parse($record->from_date)->startOfDay()->diffInDays(Carbon::parse($record->to_date)->startOfDay())+1,0).'day(s)'),
                Tables\Columns\TextColumn::make('from_soc')
                    ->label('From SOC(%)')
                    ->toggleable(true),
                Tables\Columns\TextColumn::make('to_soc')
                    ->label('To SOC (%)')
                    ->toggleable(true),
                Tables\Columns\TextColumn::make('charge')
                    ->label('Charge (%)')
                    ->toggleable(true),
                Tables\Columns\TextColumn::make('discharge')
                    ->label('Discharge (%)')
                    ->toggleable(true),
                Tables\Columns\TextColumn::make('a_regen')
                    ->label('Regen(kWh)')
                    ->toggleable()
                    ->toggledHiddenByDefault(),
                Tables\Columns\TextColumn::make('a_charge')
                    ->label('Acc Charge')
                    ->toggleable()
                    ->toggledHiddenByDefault(),
                Tables\Columns\TextColumn::make('a_discharge')
                    ->label('Acc Discharge')
                    ->toggleable()
                    ->toggledHiddenByDefault(),
                Tables\Columns\TextColumn::make('consumption')
                    ->label('kWh/100km'),
                Tables\Columns\TextColumn::make('distance')
                    ->label('Distance (km)')
                    ->summarize(Tables\Columns\Summarizers\Sum::make()),
            ])
            ->defaultSort('from_date','DESC')
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
            'index' => Pages\ManageChargingCycles::route('/'),
            'view' => Pages\ViewChargeCycle::route('/{record}'),
        ];
    }
}
