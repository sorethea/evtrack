<?php

namespace App\Filament\Resources;

use App\Filament\Resources\Obd2LogsResource\Pages;
use App\Filament\Resources\Obd2LogsResource\RelationManagers;
use App\Models\Obd2Logs;
use BackedEnum;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class Obd2LogsResource extends Resource
{
    protected static ?string $model = Obd2Logs::class;

    public static function navigationLabel(?string $label): void
    {
        trans('ev.trips');
    }

    protected static string | BackedEnum | null $navigationIcon = 'heroicon-o-cloud-arrow-down';

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
                Tables\Columns\TextColumn::make("seconds")
                    ->time()
                    ->searchable(),
                Tables\Columns\TextColumn::make("pid")
                    ->searchable(),
                Tables\Columns\TextColumn::make("value")
                    ->numeric(),
            ])
            ->filters([
                //
            ])
            ->actions([
                //\Filament\Actions\EditAction::make(),
            ])
            ->defaultSort('id','desc')
            ->bulkActions([
                \Filament\Actions\BulkActionGroup::make([
                    \Filament\Actions\DeleteBulkAction::make(),
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
            'index' => Pages\ListObd2Logs::route('/'),
        ];
    }
}
