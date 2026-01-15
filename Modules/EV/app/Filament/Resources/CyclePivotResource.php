<?php

namespace Modules\EV\Filament\Resources;


use Modules\EV\Filament\Resources\CyclePivotResource\Pages;
use Modules\EV\Filament\Resources\CyclePivotResource\RelationManagers;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Modules\EV\Models\CycleCompleteAnalytics;

class CyclePivotResource extends Resource
{
    protected static ?string $model = CycleCompleteAnalytics::class;

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
                Tables\Columns\ColumnGroup::make('Date')->columns([
                    Tables\Columns\TextColumn::make('cycle_start_date')
                        ->label(trans('ev.from'))
                        ->date(),
                    Tables\Columns\TextColumn::make('cycle_end_date')
                        ->label(trans('ev.to'))
                        ->date(),
                ]),
                Tables\Columns\ColumnGroup::make('SoC')->columns([
                    Tables\Columns\TextColumn::make('start_soc')
                        ->label(trans('ev.from'))
                        ->numeric(1),
                    Tables\Columns\TextColumn::make('end_soc')
                        ->label(trans('ev.to'))
                        ->numeric(1),
                ]),
                Tables\Columns\TextColumn::make('total_logs')
                    ->label(trans("ev.count"))
                    ->numeric(0),
                Tables\Columns\TextColumn::make('distance_km')
                    ->label(trans("ev.distance"))
                    ->numeric(1),
                Tables\Columns\TextColumn::make('ac_delta')
                    ->label(trans("ev.charge"))
                    ->numeric(0),
                Tables\Columns\TextColumn::make('ac_epsilon')
                    ->label(trans("ev.next_charge"))
                    ->numeric(0),
                Tables\Columns\TextColumn::make('ad_delta')
                    ->label(trans("ev.discharge"))
                    ->numeric(0),
            ])
            ->filters([
                //
            ])
            ->actions([
                //Tables\Actions\EditAction::make(),
            ])
            ->defaultSort('cycle_start_date','desc')
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    //Tables\Actions\DeleteBulkAction::make(),
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
            'index' => Pages\ListCyclePivots::route('/'),
//            'create' => Pages\CreateCyclePivot::route('/create'),
//            'edit' => Pages\EditCyclePivot::route('/{record}/edit'),
        ];
    }
}
