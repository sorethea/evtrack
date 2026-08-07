<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ChargeResource\Pages;
use App\Filament\Resources\ChargeResource\RelationManagers;
use App\Filament\Resources\ChargeResource\Widgets\ChargeCost;
use App\Filament\Resources\ChargeResource\Widgets\ChargeOverview;
use App\Models\Charge;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Fieldset;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class ChargeResource extends Resource
{
    protected static ?string $model = Charge::class;

    protected static bool $shouldRegisterNavigation = false;

    protected static string | BackedEnum | null $navigationIcon= 'heroicon-o-bolt';

    public static function form(Schema $form): Schema
    {
        return $form
            ->schema([
                Section::make([
                    DatePicker::make('date')
                        ->label(trans('ev.date'))
                        ->default(now())
                        ->required(),
                    Select::make('type')
                        ->options(trans("ev.charge_types"))
                        ->default('ac')
                        ->required(),
                    Fieldset::make("soc")
                        ->label(trans("ev.soc"))
                        ->schema([
                        TextInput::make("soc_from")
                            ->label(trans("ev.from"))
                            ->default(0)
                            ->nullable(),
                        TextInput::make("soc_to")
                            ->label(trans("ev.to"))
                            ->default(0)
                            ->nullable(),
                    ]),
                    Fieldset::make("accumulative")
                        ->label(trans("ev.accumulative"))
                        ->schema([
                        TextInput::make("ac_from")
                            ->label(trans("ev.from"))
                            ->default(0)
                            ->nullable(),
                        TextInput::make("ac_to")
                            ->label(trans("ev.to"))
                            ->default(0)
                            ->nullable(),
                    ]),
                    TextInput::make("qty")
                        ->default(0),
                    TextInput::make("price")
                        ->default(0),

                ])->columns(2),

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
                    ->searchable(),
                Tables\Columns\TextColumn::make("qty")
                    ->label(trans("ev.qty"))
                    ->numeric(0)
                    ->suffix("kWh"),
                Tables\Columns\TextColumn::make("price")
                    ->numeric(0)
                    ->label(trans("ev.price")),
                Tables\Columns\TextColumn::make("total_price")
                    ->default(fn($record)=>$record->qty * $record->price)
                    ->numeric(0)
                    ->label(trans("ev.total_price")),
            ])
            ->filters([
                //
            ])
            ->defaultSort('date','desc')
            ->actions([
                ViewAction::make(),
                EditAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getWidgets(): array
    {
        return [
            ChargeOverview::class,
        ];
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
