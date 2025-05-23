<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TripResource\Pages;
use App\Filament\Resources\TripResource\RelationManagers;
use App\Models\Trip;
use Carbon\Carbon;
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

    protected static bool $shouldRegisterNavigation = false;

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
                    Forms\Components\Section::make(trans("ev.odo"))
                        ->schema([
                            Forms\Components\TextInput::make('odo_from')
                                ->label(trans("ev.from"))
                                ->default(fn()=>auth()->user()->vehicle->odo)
                                ->required(),
                            Forms\Components\TextInput::make('odo_to')
                                ->label(trans("ev.to"))
                                ->required(),
                        ])
                        ->columns(2),
                    Forms\Components\Section::make(trans("ev.soc"))
                        ->schema([
                            Forms\Components\TextInput::make('soc_from')
                                ->label(trans("ev.from"))
                                ->default(fn()=>auth()->user()->vehicle->soc)
                                ->required(),
                            Forms\Components\TextInput::make('soc_to')
                                ->label(trans("ev.to"))
                                ->required(),
                        ])
                        ->columns(2),
                    Forms\Components\Section::make(trans("ev.accumulative"))
                        ->schema([
                            Forms\Components\Fieldset::make(trans("ev.charge"))
                                ->schema([
                                    Forms\Components\TextInput::make('ac_from')
                                        ->label(trans("ev.from"))
                                        ->nullable(),
                                    Forms\Components\TextInput::make('ac_to')
                                        ->label(trans("ev.to"))
                                        ->nullable(),
                                ]),
                            Forms\Components\Fieldset::make(trans("ev.discharge"))
                                ->schema([
                                    Forms\Components\TextInput::make('ad_from')
                                        ->label(trans("ev.from"))
                                        ->nullable(),
                                    Forms\Components\TextInput::make('ad_to')
                                        ->label(trans("ev.to"))
                                        ->nullable(),
                                ]),


                        ]),
                    Forms\Components\MarkdownEditor::make("comment")
                        ->label(trans("ev.comment"))
                        ->columnSpan(2)
                        ->nullable(),

                ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make(trans("ev.date"))
                    ->tooltip(fn($record)=>Carbon::parse($record->date_from)->format("d M, Y")." to ".Carbon::parse($record->date_to)->format('d M, Y'))
                    ->default(fn($record)=>Carbon::parse($record->date_from)->diffInDays(Carbon::parse($record->date_to))+1)
                    ->suffix('day(s)'),
                Tables\Columns\TextColumn::make(trans("ev.soc"))
                    ->suffix("%")
                    ->default(fn($record)=>$record->soc_from."-".$record->soc_to),
                Tables\Columns\TextColumn::make(trans("ev.distance"))
                    ->default(fn($record)=>$record->odo_to - $record->odo_from)
                    ->suffix('Km'),
                Tables\Columns\TextColumn::make(trans("ev.charge"))
                    ->default(fn($record)=>$record->ac_to - $record->ac_from)
                    ->suffix("kWh"),
//                Tables\Columns\TextColumn::make(trans("ev.net")." ".trans("ev.discharge"))
//                    ->default(fn($record)=>$record->vehicle->capacity * ($record->soc_from - $record->soc_to)/100)
//                    ->numeric(0)
//                    ->suffix("kWh"),
                Tables\Columns\TextColumn::make(trans("ev.discharge"))
                    ->default(fn($record)=>$record->ad_to - $record->ad_from)
                    ->numeric(0)
                    ->suffix("kWh"),
                Tables\Columns\TextColumn::make(trans("ev.consumption"))
                    ->default(function ($record){
                        $distance = $record->odo_to - $record->odo_from;
                        $discharge = $record->ad_to - $record->ad_from;
                        return $discharge/$distance *1000;
                    })
                    ->numeric(0)
                    ->suffix("Wh/Km"),
//                Tables\Columns\TextColumn::make(trans("ev.range"))
//                    ->default(function ($record){
//                        $distance = $record->odo_to - $record->odo_from;
//                        $discharge = $record->soc_from - $record->soc_to;
//                        return $distance/$discharge * 100;
//                    })
//                    ->numeric(0)
//                    ->suffix("Km"),
            ])
            ->defaultSort("date_to","desc")
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
            'index' => Pages\ListTrips::route('/'),
            'create' => Pages\CreateTrip::route('/create'),
            'view' => Pages\ViewTrip::route('/{record}'),
            'edit' => Pages\EditTrip::route('/{record}/edit'),
        ];
    }
}
