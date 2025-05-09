<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TripResource\Pages;
use App\Filament\Resources\TripResource\RelationManagers;
use App\Models\Trip;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class TripResource extends Resource
{
    protected static ?string $model = Trip::class;

    protected static ?string $navigationIcon = 'heroicon-o-map';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make([
                    Forms\Components\Section::make([
                        Forms\Components\DatePicker::make('date_from')
                            ->label(trans("ev.from"))
                            ->default(now())
                            ->required(),
                        Forms\Components\DatePicker::make('date_to')
                            ->label(trans("ev.to"))
                            ->default(now())
                            ->nullable(),
                    ])
                        ->columns(2)
                        ->heading(trans("ev.date")),

                    Forms\Components\TextInput::make('odo_from')
                        ->default(fn()=>auth()->user()->vehicle->odo)
                        ->required(),
                    Forms\Components\TextInput::make('odo_to')
                        ->required(),
                    Forms\Components\TextInput::make('soc_from')
                        ->default(fn()=>auth()->user()->vehicle->soc)
                        ->required(),
                    Forms\Components\TextInput::make('soc_to')
                        ->required(),
                    Forms\Components\TextInput::make('ac_from')
                        ->nullable(),
                    Forms\Components\TextInput::make('ac_to')
                        ->nullable(),
                    Forms\Components\TextInput::make('ad_from')
                        ->nullable(),
                    Forms\Components\TextInput::make('ad_to')
                        ->nullable(),
                ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                //
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
            'index' => Pages\ListTrips::route('/'),
            'create' => Pages\CreateTrip::route('/create'),
            'edit' => Pages\EditTrip::route('/{record}/edit'),
        ];
    }
}
