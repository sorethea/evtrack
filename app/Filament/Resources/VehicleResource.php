<?php

namespace App\Filament\Resources;

use App\Filament\Resources\VehicleResource\Pages;
use App\Filament\Resources\VehicleResource\RelationManagers;
use App\Models\Vehicle;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

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
            'index' => Pages\ListVehicles::route('/'),
            'create' => Pages\CreateVehicle::route('/create'),
            'view' => Pages\ViewVehicle::route('/{record}'),
            'edit' => Pages\EditVehicle::route('/{record}/edit'),
        ];
    }
}
