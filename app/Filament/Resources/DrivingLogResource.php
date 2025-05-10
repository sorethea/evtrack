<?php

namespace App\Filament\Resources;

use App\Filament\Resources\DrivingLogResource\Pages;
use App\Filament\Resources\DrivingLogResource\RelationManagers;
use App\Models\DrivingLog;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class DrivingLogResource extends Resource
{
    protected static ?string $model = DrivingLog::class;

    protected static ?string $navigationIcon = 'heroicon-o-pencil-square';

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
                Tables\Columns\TextColumn::make("date")
                    ->date('d M, Y')
                    ->searchable(),
                Tables\Columns\TextColumn::make("type")
                    ->label(trans('ev.type'))
                    ->searchable(),
                Tables\Columns\TextColumn::make("odo")
                    ->label(trans('ev.odo'))
                    ->searchable(),
                Tables\Columns\TextColumn::make("soc_from")
                    ->label(trans('ev.soc').' '.trans('ev.from'))
                    ->searchable(),
                Tables\Columns\TextColumn::make("soc_to")
                    ->label(trans('ev.soc').' '.trans('ev.to'))
                    ->searchable(),
                Tables\Columns\TextColumn::make("ac")
                    ->label(trans('ev.charge'))
                    ->searchable(),
                Tables\Columns\TextColumn::make("ad")
                    ->label(trans('ev.discharge'))
                    ->searchable(),
            ])
            ->filters([
                //
            ])
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
            'index' => Pages\ListDrivingLogs::route('/'),
            'create' => Pages\CreateDrivingLog::route('/create'),
            'edit' => Pages\EditDrivingLog::route('/{record}/edit'),
        ];
    }
}
