<?php

namespace Modules\EV\Filament\Resources;

use Modules\EV\Filament\Resources\VehicleResource\Pages;
use Modules\EV\Filament\Resources\VehicleResource\RelationManagers;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Modules\EV\Models\Vehicle;

class VehicleResource extends Resource
{
    protected static ?string $model = Vehicle::class;

    protected static ?string $navigationIcon = 'heroicon-o-truck';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make([
                    Forms\Components\TextInput::make("name")->required(),
                    Forms\Components\TextInput::make("make")->required(),
                    Forms\Components\TextInput::make("model")->required(),
                    Forms\Components\TextInput::make("year")->required(),
                    Forms\Components\TextInput::make("soc")->name(trans("ev.soc"))->nullable(),
                    Forms\Components\TextInput::make("odo")->name(trans("ev.odo"))->nullable(),
                    Forms\Components\TextInput::make("vin")->nullable(),
                    Forms\Components\TextInput::make("plate")->nullable(),
                    Forms\Components\TextInput::make("capacity")->name(trans("ev.capacity"))->suffix("kWh")->nullable(),
                    Forms\Components\Toggle::make("is_default")->default(false),
                    Forms\Components\MarkdownEditor::make("specs")
                        ->columnSpan(2)
                        ->nullable(),
                ])
                ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make("make")->searchable(),
                Tables\Columns\TextColumn::make("model")->searchable(),
                Tables\Columns\TextColumn::make("year")->searchable(),
                Tables\Columns\TextColumn::make("vin")->searchable(),
                Tables\Columns\TextColumn::make("plate")->searchable(),
                Tables\Columns\TextColumn::make("capacity")->suffix("kWh")->searchable(),
                Tables\Columns\IconColumn::make("is_default")->boolean(),
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
            'index' => \Modules\EV\Filament\Resources\VehicleResource\Pages\ListVehicles::route('/'),
            'create' => \Modules\EV\Filament\Resources\VehicleResource\Pages\CreateVehicle::route('/create'),
            'view' => \Modules\EV\Filament\Resources\VehicleResource\Pages\ViewVehicle::route('/{record}'),
            'edit' => \Modules\EV\Filament\Resources\VehicleResource\Pages\EditVehicle::route('/{record}/edit'),
        ];
    }
}
