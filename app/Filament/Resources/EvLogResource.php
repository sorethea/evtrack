<?php

namespace App\Filament\Resources;

use App\Filament\Resources\EvLogResource\Pages;
use App\Filament\Resources\EvLogResource\RelationManagers;
use App\Models\EvLog;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Number;

class EvLogResource extends Resource
{
    protected static ?string $model = EvLog::class;

    protected static ?string $navigationIcon = 'heroicon-o-calendar';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make([
                    Forms\Components\DatePicker::make("date")
                        ->label(trans('ev.date'))
                        ->default(date())
                        ->required(),
                    Forms\Components\TimePicker::make("seconds")
                        ->label(trans('ev.time'))
                        ->nullable(),
                    Forms\Components\Select::make("parent_id")
                        ->label(trans('ev.parent'))
                        ->relationship('parent','date')
                        ->default(fn()=>EvLog::max('date'))
                        ->searchable()
                        ->nullable(),
                    Forms\Components\Select::make("type")
                        ->label(trans('ev.type'))
                        ->options(trans("ev.log_types"))
                        ->default('log')
                        ->nullable(),
                    Forms\Components\TextInput::make("odo")
                        ->label(trans('ev.odo'))
                        ->required(),
                    Forms\Components\TextInput::make("soc")
                        ->label(trans('ev.soc'))
                        ->required(),
                    Forms\Components\TextInput::make("ac")
                        ->label(trans('ev.charge'))
                        ->nullable(),
                    Forms\Components\TextInput::make("ad")
                        ->label(trans('ev.discharge'))
                        ->nullable(),
                    Forms\Components\TextInput::make("voltage")
                        ->label(trans('ev.voltage'))
                        ->nullable(),

                ])->columns(2)
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
                    ->label(trans('ev.type'))
                    ->formatStateUsing(fn(string $state):string =>trans("ev.log_types.{$state}"))
                    ->searchable(),
                Tables\Columns\TextColumn::make('distance')
                    ->label(trans('ev.distance'))
                    ->default(fn ($record)=>Number::format(!empty($record?->parent?->odo)?$record->odo - $record?->parent?->odo:$record->odo,0)."km"),
                Tables\Columns\TextColumn::make('soc')
                    ->label(trans('ev.soc'))
                    ->searchable(),
                Tables\Columns\TextColumn::make('consumption')
                    ->label(trans('ev.consumption'))
                    ->default(fn ($record)=>Number::format(!empty($record?->parent?->soc)?$record->soc - $record?->parent?->soc:0,1)."%"),
            ])
            ->filters([
                //
            ])
            ->defaultSort('date','desc')
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
            'index' => Pages\ListEvLogs::route('/'),
            'create' => Pages\CreateEvLog::route('/create'),
            'edit' => Pages\EditEvLog::route('/{record}/edit'),
        ];
    }
}
