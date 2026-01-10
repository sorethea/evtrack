<?php

namespace Modules\EV\Filament\Resources;

use Modules\EV\Filament\Resources\LogResource\Pages;
use Modules\EV\Filament\Resources\LogResource\RelationManagers;
use Modules\EV\Models\EvLog;
use Modules\EV\Models\Log;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class LogResource extends Resource
{
    protected static ?string $model = EvLog::class;


    /**
     * @param string|null $navigationLabel
     */
    public static function setNavigationLabel(?string $navigationLabel): void
    {
        self::$navigationLabel = "Daily Logs";
    }

    protected static ?string $navigationIcon = 'heroicon-o-table-cells';

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
                    ->date('d M, Y H:i')
                    ->searchable(),
                Tables\Columns\TextColumn::make("log_type")
                    ->badge()
                    ->color(fn(string $state) => match ($state) {
                        'charging' => 'success',
                        'driving' => 'info',
                        'packing' => 'warning',
                    })
                    ->label(trans('ev.type'))
                    ->formatStateUsing(fn(string $state): string => trans("ev.log_types.options.{$state}"))
                    ->searchable(),
                Tables\Columns\ColumnGroup::make('Accumulative',[
                    Tables\Columns\TextColumn::make('ac')
                        ->numeric(0)
                        ->suffix('kWh')
                        ->label('Charge'),
                    Tables\Columns\TextColumn::make('ad')
                        ->numeric(0)
                        ->suffix('kWh')
                        ->label('Discharge'),
                    Tables\Columns\TextColumn::make('ac_power')
                        ->numeric(0)
                        ->suffix('Ah')
                        ->label('Charge'),
                    Tables\Columns\TextColumn::make('ad_power')
                        ->numeric(0)
                        ->suffix('Ah')
                        ->label('Discharge'),

                ])
            ])
            ->filters([
                //
            ])
            ->actions([
                //Tables\Actions\EditAction::make(),
            ])
            ->defaultSort('date','desc')
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
            'index' => Pages\ListLogs::route('/'),
            //'create' => Pages\CreateLog::route('/create'),
            //'edit' => Pages\EditLog::route('/{record}/edit'),
        ];
    }
}
