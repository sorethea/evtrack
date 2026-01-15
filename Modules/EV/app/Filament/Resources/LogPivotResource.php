<?php

namespace Modules\EV\Filament\Resources;

use Modules\EV\Filament\Resources\LogPivotResource\Pages;
use Modules\EV\Filament\Resources\LogPivotResource\RelationManagers;
use Modules\EV\Models\LogPivot;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class LogPivotResource extends Resource
{
    protected static ?string $model = LogPivot::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

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
                Tables\Columns\TextColumn::make("date")->date()->sortable(),
                Tables\Columns\TextColumn::make("cycle.date")->date(),
                Tables\Columns\TextColumn::make("log_type")->searchable(),
                Tables\Columns\TextColumn::make("odo")
                    ->label(trans("ev.odo"))
                    ->numeric(1),
                Tables\Columns\TextColumn::make("voltage")
                    ->label(trans("ev.voltage"))
                    ->numeric(0),
                Tables\Columns\ColumnGroup::make(trans("ev.soc"))
                    ->columns([
                        Tables\Columns\TextColumn::make("parent.soc")
                            ->numeric(1)
                            ->label(trans("ev.from")),
                        Tables\Columns\TextColumn::make("soc")
                            ->numeric(1)
                            ->label(trans("ev.to")),
                    ]),
                Tables\Columns\ColumnGroup::make("accumulative")
                    ->label(trans("ev.accumulative"))
                    ->columns([
                    Tables\Columns\TextColumn::make("ac")
                        ->label(trans("ev.charge"))
                        ->numeric(),
                    Tables\Columns\TextColumn::make("ad")
                        ->label(trans("ev.discharge"))
                        ->numeric(),
                ]),
                Tables\Columns\ColumnGroup::make("cell_voltage")
                    ->label(trans("ev.cell_voltage"))
                    ->columns([
                    Tables\Columns\TextColumn::make("lvc")
                        ->label(trans("ev.lowest"))
                        ->numeric(3),
                    Tables\Columns\TextColumn::make("hvc")
                        ->label(trans("ev.highest"))
                        ->numeric(3),
                ]),

            ])
            ->filters([
                //
            ])
            ->actions([
                //Tables\Actions\EditAction::make(),
            ])
            ->defaultSort('date','desc')
            ->bulkActions([
//                Tables\Actions\BulkActionGroup::make([
//                    Tables\Actions\DeleteBulkAction::make(),
//                ]),
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
            'index' => Pages\ListLogPivots::route('/'),
            'view' =>Pages\ViewLogPivot::route('/{record}')
//            'create' => Pages\CreateLogPivot::route('/create'),
//            'edit' => Pages\EditLogPivot::route('/{record}/edit'),
        ];
    }
}
