<?php

namespace App\Filament\Resources;

use App\Filament\Resources\DrivingLogResource\Pages;
use App\Filament\Resources\DrivingLogResource\RelationManagers;
use App\Filament\Resources\DrivingLogResource\Widgets\DrivingOverview;
use App\Models\DrivingLog;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Number;

class DrivingLogResource extends Resource
{
    protected static ?string $model = DrivingLog::class;

    protected static ?string $navigationIcon = 'heroicon-o-pencil-square';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make([
                    Forms\Components\DatePicker::make("date")
                        ->label(trans('ev.date'))
                        ->required(),
                    Forms\Components\TextInput::make("odo")
                        ->label(trans('ev.odo'))
                        ->required(),
                    Forms\Components\TextInput::make("soc_from")
                        ->label(trans('ev.soc_from'))
                        ->default(function (){
                            $maxDate = DrivingLog::max('date');
                            $log = DrivingLog::where("date","=",$maxDate)->first();
                            return $log->soc_to;
                        })
                        ->nullable(),
                    Forms\Components\TextInput::make("soc_to")
                        ->label(trans('ev.soc_to'))
                        ->nullable(),
                    Forms\Components\TextInput::make("ac")
                        ->label(trans('ev.charge'))
                        ->nullable(),
                    Forms\Components\TextInput::make("ad")
                        ->label(trans('ev.discharge'))
                        ->nullable(),
                    Forms\Components\TextInput::make("voltage")
                        ->label(trans('ev.voltage'))
                        ->nullable(),
                    Forms\Components\Select::make("type")
                        ->label(trans('ev.type'))
                        ->options(trans("ev.log_types"))
                        ->default('log')
                        ->nullable(),

                ])->columns(2)
            ]);
    }
    protected static bool $shouldRegisterNavigation = false;
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
                Tables\Columns\TextColumn::make("distance")
                    ->label(trans('ev.distance'))
                    ->default(function ($record){
                        $preRecord = DrivingLog::find($record->id -1);
                        return Number::format($record->odo - $preRecord->odo,0).'km';
                    })
                    ->numeric()
                    ->searchable(),
                Tables\Columns\TextColumn::make("voltage")
                    ->label(trans('ev.voltage'))
                    ->searchable(),
                Tables\Columns\TextColumn::make("soc_from")
                    ->label(trans('ev.soc_from'))
                    ->searchable(),
                Tables\Columns\TextColumn::make("soc_to")
                    ->label(trans('ev.soc_to'))
                    ->searchable(),
                Tables\Columns\TextColumn::make("ac")
                    ->label(trans('ev.charge'))
                    ->numeric()
                    ->searchable(),
                Tables\Columns\TextColumn::make("ad")
                    ->label(trans('ev.discharge'))
                    ->numeric()
                    ->searchable(),
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
    public static function getWidgets(): array
    {
        return [
            DrivingOverview::class,
        ];
    }


    public static function getPages(): array
    {
        return [
            'index' => Pages\ListDrivingLogs::route('/'),
            'create' => Pages\CreateDrivingLog::route('/create'),
            'edit' => Pages\EditDrivingLog::route('/{record}/edit'),
        ];
    }
}
