<?php

namespace Modules\EV\Filament\Resources;

use Filament\Forms;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Number;
use Modules\EV\Models\EvLog;

class EvLogResource extends Resource
{
    use Tables\Concerns\InteractsWithTable;

    protected static ?string $model = EvLog::class;

    protected static ?string $navigationIcon = 'heroicon-o-calendar';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make([
                    Forms\Components\DateTimePicker::make("date")
                        ->label(trans('ev.date'))
                        ->default(now()->format('Y-m-d H i'))
                        ->required(),
                    Forms\Components\Select::make("parent_id")
                        ->live()
                        ->label(trans('ev.parent'))
                        ->relationship('parent', 'date')
                        ->default(fn() => EvLog::max('date'))
                        ->searchable()
                        ->nullable(),
                    Forms\Components\Select::make("log_type")
                        ->live()
                        ->label(trans('ev.log_types.name'))
                        ->options(trans("ev.log_types.options"))
                        ->default('driving')
                        ->nullable(),
                    Forms\Components\Select::make("cycle_id")
                        ->reactive()
                        ->label(trans('ev.cycle'))
                        ->relationship('cycle', 'date')
                        ->hidden(fn(Get $get) => $get("log_type") == "charging")
                        ->default(fn() => EvLog::where("log_type", "charging")->max('date'))
                        ->searchable()
                        ->nullable(),
//                    Forms\Components\TextInput::make("odo")
//                        ->label(trans('ev.odo'))
//                        ->required(),
//                    Forms\Components\TextInput::make("soc")
//                        ->label(trans('ev.soc'))
//                        ->nullable(),
//                    Forms\Components\TextInput::make("soc_actual")
//                        ->label(trans('ev.soc_actual'))
//                        ->nullable(),
                    Forms\Components\Select::make("charge_type")
                        ->label(trans('ev.charge_types.name'))
                        ->options(trans("ev.charge_types.options"))
                        ->hidden(fn(Get $get) => $get("log_type") != "charging")
                        ->nullable(),
//                    Forms\Components\Repeater::make('items')
//                        ->relationship('items')
//                        ->orderColumn(column: 'item_id')
//                        ->schema([
//                            Forms\Components\Select::make('item_id')
//                                ->relationship('item','pid')
//                                ->required(),
//                            Forms\Components\TextInput::make('value')->default(0)
//                        ])
//                        ->columns(2)
//                        ->columnSpan(2),
//                    Forms\Components\Fieldset::make()->label(trans('ev.obd2'))
//                    ->schema([
//                        Forms\Components\TextInput::make("ac")
//                            ->label(trans('ev.charge'))
//                            ->nullable(),
//                        Forms\Components\TextInput::make("ad")
//                            ->label(trans('ev.discharge'))
//                            ->nullable(),
//                        Forms\Components\TextInput::make("highest_temp_cell")
//                            ->label(trans('ev.highest_temp_cell'))
//                            ->nullable(),
//                        Forms\Components\TextInput::make("lowest_temp_cell")
//                            ->label(trans('ev.lowest_temp_cell'))
//                            ->nullable(),
//                        Forms\Components\TextInput::make("highest_volt_cell")
//                            ->label(trans('ev.highest_volt_cell'))
//                            ->nullable(),
//                        Forms\Components\TextInput::make("lowest_volt_cell")
//                            ->label(trans('ev.lowest_volt_cell'))
//                            ->nullable(),
//                        Forms\Components\TextInput::make("voltage")
//                            ->label(trans('ev.voltage'))
//                            ->nullable(),
//                    ]),
                    Forms\Components\Textarea::make("remark")
                        ->label(trans('ev.remark'))
                        ->columnSpan(2)
                        ->nullable(),

                ])->columns(2)
            ]);
    }


    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make("date")
                    ->date('d M, Y H:i')
                    ->searchable(),
                Tables\Columns\TextColumn::make("log_type")
                    ->badge()
                    ->color(fn(string $state) => match ($state) {
                        'charging' => 'success',
                        'driving' => 'info',
                        'packing' => 'warning',
                    })
                    ->label(trans('ev.type'))
                    ->formatStateUsing(fn(string $state): string => trans("ev.log_types.options.{$state}"))
                    ->searchable(),
                Tables\Columns\ColumnGroup::make('SoC(%)',[
                    Tables\Columns\TextColumn::make('parent.detail.soc')
                        ->inverseRelationship('log')
                        ->numeric(1)
                        ->label(trans('ev.from') )
                        ->toggleable(isToggledHiddenByDefault: false),
                        //->summarize(Tables\Columns\Summarizers\Summarizer::make()->using(fn(\Illuminate\Database\Query\Builder $query)=>$query->max('parent_soc'))),
                    Tables\Columns\TextColumn::make('detail.soc')
                        ->inverseRelationship('log')
                        ->numeric(1)
                        ->label(trans('ev.to') )
                        ->toggleable(isToggledHiddenByDefault: false),
//                        ->summarize(Tables\Columns\Summarizers\Summarizer::make()->using(fn(\Illuminate\Database\Query\Builder
//                            $query)=>$query
//                            ->select('SELECT l.id AS id,
//                                  l.date AS date,
//                                  MIN(li.value) AS soc
//                                FROM ev_logs AS l
//                                GROUP BY li.item_id
//                                LEFT JOIN ev_log_items AS li
//                                  ON l.id = li.log_id
//                                  AND li.item_id = 11;')->value('soc')
//                        )),
                    Tables\Columns\TextColumn::make('detail.soc_derivation')
                        ->inverseRelationship('log')
                        ->label(trans('ev.soc_derivation'))
                        ->numeric(1)
                        ->summarize(Tables\Columns\Summarizers\Sum::make()->label(trans('ev.soc_derivation')))
                        ->toggleable(),
                    Tables\Columns\TextColumn::make('detail.soc_middle')
                        ->label(trans('ev.soc_middle') )
                        ->numeric(1)
                        ->inverseRelationship('log')
                        ->toggleable(),
                    Tables\Columns\TextColumn::make('detail.consumption')
                        ->numeric(1)
                        ->formatStateUsing(fn($state)=>($state>0)?Number::format($state,1):0)
                        ->label(__('ev.consumption'))
                        ->toggleable(),
                ]),
                Tables\Columns\ColumnGroup::make(trans('ev.accumulative').'(Ah)',[
                    Tables\Columns\TextColumn::make('detail.charge_amp')
                        ->inverseRelationship('log')
                        ->numeric(1)
                        ->label(trans('ev.charge') )
                        ->summarize(Tables\Columns\Summarizers\Sum::make()->label(trans('ev.charge'))),
                    Tables\Columns\TextColumn::make('detail.discharge_amp')
                        ->inverseRelationship('log')
                        ->numeric(1)
                        ->label(trans('ev.discharge') )
                        ->summarize(Tables\Columns\Summarizers\Sum::make()->label(trans('ev.discharge'))),
                    Tables\Columns\TextColumn::make('detail.a_consumption_amp')
                        ->numeric(1)
                        ->formatStateUsing(fn($state)=>($state>0)?Number::format($state,1):0)
                        ->label(__('ev.consumption'))
                        ->toggleable(),
                    Tables\Columns\TextColumn::make('detail.capacity_amp')
                        ->formatStateUsing(fn($state)=>Number::format($state,1))
                        ->inverseRelationship('log')
                        ->label(trans('ev.capacity')),
                ]),
                Tables\Columns\ColumnGroup::make(trans('ev.accumulative').'(kWh)',[
                    Tables\Columns\TextColumn::make('detail.charge')
                        ->inverseRelationship('log')
                        ->numeric(1)
                        ->label(trans('ev.charge') )
                        ->summarize(Tables\Columns\Summarizers\Sum::make()->label(trans('ev.charge'))),
                    Tables\Columns\TextColumn::make('detail.discharge')
                        ->inverseRelationship('log')
                        ->numeric(1)
                        ->label(trans('ev.discharge') )
                        ->summarize(Tables\Columns\Summarizers\Sum::make()->label(trans('ev.discharge'))),
                    Tables\Columns\TextColumn::make('detail.middle')
                        ->numeric()
                        ->label(__('ev.soc_middle'))
                        ->toggleable(),
                    Tables\Columns\TextColumn::make('detail.a_consumption')
                        ->numeric(1)
                        ->formatStateUsing(fn($state)=>($state>0)?Number::format($state,1):0)
                        ->label(__('ev.consumption'))
                        ->toggleable(),
                    Tables\Columns\TextColumn::make('detail.capacity')
                        ->formatStateUsing(fn($state)=>Number::format($state,1))
                        ->inverseRelationship('log')
                        ->label(trans('ev.capacity')),
                ]),
                Tables\Columns\ColumnGroup::make(trans('ev.voltage').'(V)',[
                    Tables\Columns\TextColumn::make('detail.lvc')
                        ->badge()
                        ->numeric(3)
                        ->inverseRelationship('log')
                        ->color(fn(string $state) => $state >3.2 && $state <3.6  ? 'success' : ($state <= 3.2 && $state>3.1 ? 'warning' : 'danger'))
                        ->label(trans('ev.lowest'))
                        ->toggleable(isToggledHiddenByDefault: true),
                    Tables\Columns\TextColumn::make('detail.hvc')
                        ->badge()
                        ->numeric(3)
                        ->inverseRelationship('log')
                        ->color(fn(string $state) => $state >3.2 && $state <3.6  ? 'success' : ($state < 3.7 && $state>=3.6 ? 'warning' : 'danger'))
                        ->label(trans('ev.highest'))
                        ->toggleable(isToggledHiddenByDefault: true),
                    Tables\Columns\TextColumn::make('detail.v_spread')
                        ->label(trans('ev.spread'))
                        ->numeric(3)
                        ->inverseRelationship('log')
                        ->badge()
                        ->color(fn(string $state) => $state < 0.1 ? 'success' : ($state < 0.2 ? 'warning' : 'danger'))
                        ->toggleable(isToggledHiddenByDefault: true),
                ]),
                Tables\Columns\ColumnGroup::make(trans('ev.temperature').'(C)',[
                    Tables\Columns\TextColumn::make('detail.ltc')
                        ->badge()
                        ->numeric(0)
                        ->inverseRelationship('log')
                        ->color(fn(string $state) => $state >20 && $state <38  ? 'success' : ($state < 20 && $state>=10 ? 'warning' : 'danger'))
                        ->label(trans('ev.lowest'))
                        ->toggleable(isToggledHiddenByDefault: true),
                    Tables\Columns\TextColumn::make('detail.htc')
                        ->badge()
                        ->numeric(0)
                        ->inverseRelationship('log')
                        ->color(fn(string $state) => $state >20 && $state <38  ? 'success' : ($state < 40 && $state>=38 ? 'warning' : 'danger'))
                        ->label(trans('ev.highest'))
                        ->toggleable(isToggledHiddenByDefault: true),
                    Tables\Columns\TextColumn::make('detail.t_spread')
                        ->label(trans('ev.spread'))
                        ->numeric(0)
                        ->inverseRelationship('log')
                        ->badge()
                        ->color(fn(string $state) => $state <=3 ? 'success' : ($state <=5 ? 'warning' : 'danger'))
                        ->toggleable(isToggledHiddenByDefault: true),
                ]),

                Tables\Columns\TextColumn::make('detail.range')
                    ->formatStateUsing(fn($state)=>Number::format($state,1))
                    ->inverseRelationship('log')
                    ->label(trans('ev.range')),
                Tables\Columns\TextColumn::make('detail.distance')
                    ->formatStateUsing(fn($state)=>Number::format($state,1))
                    ->inverseRelationship('log')
                    ->label(trans('ev.distance'))
                    ->summarize(Tables\Columns\Summarizers\Sum::make()->label(trans('ev.distance'))),
            ])
            //->defaultGroup('cycle.date')
            ->groups([
                Tables\Grouping\Group::make('cycle.date')->date()
//                    ->orderQueryUsing(
//                        fn (Builder $query, string $direction) => $query
//                            ->selectRaw('COALESCE(cycle.date, ev_logs.date) as group_date')
//                            ->leftJoin('ev_logs as cycle', 'ev_logs.cycle_id', '=', 'cycle.id')
//                            ->orderBy('group_date', $direction),
//                        'desc' // Default to descending
//                    )
            ])
            ->defaultSort('cycle.date','desc')
            ->filters([
                Tables\Filters\QueryBuilder::make()
                    ->constraints([
                        Tables\Filters\QueryBuilder\Constraints\DateConstraint::make('date'),
                    ]),
                Tables\Filters\SelectFilter::make('log_type')
                    ->label(trans('ev.log_types.name'))
                    ->options(trans('ev.log_types.options')),
//                Tables\Filters\SelectFilter::make('charge_type')
//                    ->label(trans('ev.charge_types.name'))
//                    ->options(trans('ev.charge_types.options')),

            ])
            ->defaultSort(fn(Builder $query) => $query->orderBy('date', 'desc')->orderBy('id', 'desc'))
            ->actions([

                Tables\Actions\Action::make('obd_import')
                    ->visible(fn($record) => !$record->items()->count('*'))
                    ->icon('heroicon-o-arrow-up-tray')
                    ->label(trans('ODB2'))
                    ->form([
                        FileUpload::make('obd_file')
                            ->preserveFilenames()
                            ->disk('local')
                            ->directory('obd2'),
                    ])
                    ->action(function (array $data, Model $record) {
                        \evlog::obdImportAction($data, $record);
                    }),


                Tables\Actions\ViewAction::make(),
                Tables\Actions\Action::make('analyse')
                    ->icon('heroicon-o-chart-bar')
                    ->color('info')
                    ->url(fn($record)=>EvLogResource::getUrl("analyse",['record'=>$record] )),
                Tables\Actions\EditAction::make()->hidden(),
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
            \Modules\EV\Filament\Resources\EvLogResource\RelationManagers\ItemsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => \Modules\EV\Filament\Resources\EvLogResource\Pages\ListEvLogs::route('/'),
            'create' => \Modules\EV\Filament\Resources\EvLogResource\Pages\CreateEvLog::route('/create'),
            'edit' => \Modules\EV\Filament\Resources\EvLogResource\Pages\EditEvLog::route('/{record}/edit'),
            'view' => \Modules\EV\Filament\Resources\EvLogResource\Pages\ViewEvLog::route('/{record}'),
            'analyse' => \Modules\EV\Filament\Resources\EvLogResource\Pages\AnalyseEvLog::route('/{record}/analyse'),
        ];
    }

}
