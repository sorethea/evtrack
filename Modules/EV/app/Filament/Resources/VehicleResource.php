<?php

namespace Modules\EV\Filament\Resources;

use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\MarkdownEditor;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Modules\EV\Filament\Resources\VehicleResource\Pages;
use Modules\EV\Filament\Resources\VehicleResource\Pages\CreateVehicle;
use Modules\EV\Filament\Resources\VehicleResource\Pages\EditVehicle;
use Modules\EV\Filament\Resources\VehicleResource\Pages\ListVehicles;
use Modules\EV\Filament\Resources\VehicleResource\Pages\ViewVehicle;
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

    protected static string | BackedEnum | null $navigationIcon = 'heroicon-o-truck';

    public static function form( Schema $form): Schema
    {
        return $form
            ->schema([
                Section::make([
                    TextInput::make("name")->required(),
                    TextInput::make("make")->required(),
                    TextInput::make("model")->required(),
                    TextInput::make("year")->required(),
                    TextInput::make("soc")->name(trans("ev.soc"))->nullable(),
                    TextInput::make("odo")->name(trans("ev.odo"))->nullable(),
                    TextInput::make("vin")->nullable(),
                    TextInput::make("plate")->nullable(),
                    TextInput::make("consumption")->nullable(),
                    TextInput::make("capacity")->name(trans("ev.capacity"))->suffix("kWh")->nullable(),
                    TextInput::make("limited_capacity")->name(trans("ev.limited_capacity"))->suffix("kWh")->nullable(),
                    Toggle::make("is_default")->default(false),
                    MarkdownEditor::make("specs")
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
                ViewAction::make(),
                EditAction::make(),
            ])
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
            'index' => ListVehicles::route('/'),
            'create' => CreateVehicle::route('/create'),
            'view' => ViewVehicle::route('/{record}'),
            'edit' => EditVehicle::route('/{record}/edit'),
        ];
    }
}
