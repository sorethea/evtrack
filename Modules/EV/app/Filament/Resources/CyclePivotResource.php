<?php

namespace Modules\EV\Filament\Resources;


use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Schemas\Schema;
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

    protected static string | BackedEnum | null $navigationIcon = 'heroicon-o-rectangle-stack';
    protected static bool $shouldRegisterNavigation = false;

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
                    ->label(trans("ev.cycle_charge"))
                    ->numeric(0),
                Tables\Columns\TextColumn::make('ad_delta')
                    ->label(trans("ev.discharge"))
                    ->numeric(0),
            ])
            ->filters([
                //
            ])
            ->actions([
                //\Filament\Actions\EditAction::make(),
            ])
            ->defaultSort('cycle_start_date','desc')
            ->bulkActions([
                BulkActionGroup::make([
                    //\Filament\Actions\DeleteBulkAction::make(),
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
