<?php

namespace Modules\EV\Filament\Resources;

use BackedEnum;
use Carbon\Carbon;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Schemas\Schema;
use Modules\EV\Filament\Resources\LogCycleResource\Pages;
use Modules\EV\Filament\Resources\LogCycleResource\RelationManagers;
use Modules\EV\Models\LogCycle;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class LogCycleResource extends Resource
{
    protected static ?string $model = LogCycle::class;

    protected static string | BackedEnum | null $navigationIcon = 'heroicon-o-rectangle-stack';

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
                Tables\Columns\ColumnGroup::make('date')
                    ->label('Date')
                    ->columns([
                        Tables\Columns\TextColumn::make('start_date')
                            ->label('From')
                            ->date('Y-m-d')
                            ->searchable(),
                        Tables\Columns\TextColumn::make('end_date')
                            ->label('To')
                            ->date('Y-m-d')
                            ->searchable(),
                        Tables\Columns\TextColumn::make('days')
                            ->label('Days')
                            ->getStateUsing(fn($record)=>Carbon::parse($record->start_date)->diffInDays($record->end_date))
                            ->numeric(0),
                    ]),
                Tables\Columns\ColumnGroup::make('soc')
                    ->label('SoC')
                    ->columns([
                        Tables\Columns\TextColumn::make('rc_soc')
                            ->label('From')
                            ->searchable(),
                        Tables\Columns\TextColumn::make('clcc_soc')
                            ->label('To')
                            ->searchable(),
                    ]),
                Tables\Columns\ColumnGroup::make('gab')
                    ->label('Gab')
                    ->columns([
                        Tables\Columns\TextColumn::make('gab')
                            ->label('Current')
                            ->searchable(),
                        Tables\Columns\TextColumn::make('next_gab')
                            ->label('Next')
                            ->searchable(),
                    ]),

                Tables\Columns\TextColumn::make('charge')
                    ->numeric(),
                Tables\Columns\TextColumn::make('discharge')
                    ->numeric(),
                Tables\Columns\TextColumn::make('distance')
                    ->numeric(),
            ])
            ->filters([
                //
            ])
            ->actions([
                //Filament\Actions\EditAction::make(),
            ])
            ->defaultSort('end_date','desc')
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
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
            'index' => Pages\ListLogCycles::route('/'),
            'view'  => Pages\ViewLogCycle::route('/{record}')
            //'create' => Pages\CreateLogCycle::route('/create'),
            //'edit' => Pages\EditLogCycle::route('/{record}/edit'),
        ];
    }
}
