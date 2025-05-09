<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ChargeResource\Pages;
use App\Filament\Resources\ChargeResource\RelationManagers;
use App\Models\Charge;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class ChargeResource extends Resource
{
    protected static ?string $model = Charge::class;

    protected static ?string $navigationIcon = 'heroicon-o-bolt';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make([
                    Forms\Components\DatePicker::make('date')
                        ->label(trans('ev.date'))
                        ->required(),
                    Forms\Components\Select::make('type')
                        ->options(trans("ev.charge_types"))
                        ->default('home')
                        ->required(),
                    Forms\Components\Fieldset::make("soc")
                        ->label(trans("ev.soc"))
                        ->schema([
                        Forms\Components\TextInput::make("soc_from")
                            ->default(0)
                            ->nullable(),
                        Forms\Components\TextInput::make("soc_to")
                            ->default(0)
                            ->nullable(),
                    ]),
                    Forms\Components\Fieldset::make("accumulative")
                        ->label(trans("ev.accumulative"))
                        ->schema([
                        Forms\Components\TextInput::make("ac_from")
                            ->default(0)
                            ->nullable(),
                        Forms\Components\TextInput::make("ac_to")
                            ->default(0)
                            ->nullable(),
                    ]),
                    Forms\Components\TextInput::make("qty")
                        ->default(0),
                    Forms\Components\TextInput::make("price")
                        ->default(0),

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
                Tables\Actions\ViewAction::make(),
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
            'index' => Pages\ListCharges::route('/'),
            'create' => Pages\CreateCharge::route('/create'),
            'view' => Pages\ViewCharge::route('/{record}'),
            'edit' => Pages\EditCharge::route('/{record}/edit'),
        ];
    }
}
