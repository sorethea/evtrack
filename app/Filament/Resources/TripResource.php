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
                Tables\Columns\TextColumn::make("date_from")
                    ->date('d M, Y')
                    ->label(trans("ev.date") ." ".trans("ev.from")),
                Tables\Columns\TextColumn::make("date_to")
                    ->date('d M, Y')
                    ->label(trans("ev.date") ." ".trans("ev.to")),
                Tables\Columns\TextColumn::make(trans("ev.duration"))
                    ->default(fn($record)=>Carbon::parse($record->date_from)->diffInDays(Carbon::parse($record->date_to))+1),
                Tables\Columns\TextColumn::make(trans("ev.Distance"))
                    ->default(fn($record)=>$record->odo_to - $record->odo_from)
                    ->suffix('KM')
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
