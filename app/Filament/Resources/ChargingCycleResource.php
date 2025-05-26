<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ChargingCycleResource\Pages;
use App\Filament\Resources\ChargingCycleResource\RelationManagers;
use App\Models\ChargingCycle;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

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
                    ->date('d/m/y')
                    ->searchable(),
                Tables\Columns\TextColumn::make('to_date')
                    ->date('d/m/y')
                    ->searchable(),
                Tables\Columns\TextColumn::make('from_soc')
                    ->suffix('%'),
                Tables\Columns\TextColumn::make('to_soc')
                    ->suffix('%'),
            ])
            ->defaultSort('to_date','DESC')
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
        ];
    }
}
