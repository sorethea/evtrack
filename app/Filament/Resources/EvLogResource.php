<?php

namespace App\Filament\Resources;

use App\Filament\Resources\EvLogResource\Pages;
use App\Filament\Resources\EvLogResource\RelationManagers;
use App\Models\EvLog;
use App\Models\ObdItem;
use Carbon\Carbon;
use Doctrine\DBAL\Query\QueryBuilder;
use Filament\Forms;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Number;
use League\Csv\Reader;
use Symfony\Component\Mime\Encoder\QpContentEncoder;

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
                        ->relationship('parent','date')
                        ->default(fn()=>EvLog::max('date'))
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
                        ->relationship('cycle','date')
                        ->hidden(fn(Get $get)=>$get("log_type")!="driving")
                        ->default(fn()=>EvLog::where("log_type","charging")->max('date'))
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
                        ->hidden(fn(Get $get)=>$get("log_type")!="charging")
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
//            ->modifyQueryUsing(fn(Builder $query)=>$query
//                ->from('daily_logs_view','l')
//                ->selectRaw()
//            )
            ->columns([
                Tables\Columns\TextColumn::make("date")
                    ->date('d M, Y H:i')
                    ->searchable(),
                Tables\Columns\TextColumn::make("log_type")
                    ->badge()
                    ->color(fn(string $state) => match ($state){
                        'charging'=>'success',
                        'driving'=>'info',
                        'packing'=>'warning',
                    })
                    ->label(trans('ev.type'))
                    ->formatStateUsing(fn(string $state):string =>trans("ev.log_types.options.{$state}"))
                    ->searchable(),
                Tables\Columns\TextColumn::make('soc_from')
                    ->label(trans('ev.soc_from').'(%)')
                    ->formatStateUsing(fn($state)=>Number::format($state,1)),
                Tables\Columns\TextColumn::make('soc_to')
                    ->label(trans('ev.soc_to').'(%)')
                    ->formatStateUsing(fn($state)=>Number::format($state,1)),
//                Tables\Columns\TextColumn::make('soc_derivation')
//                    ->label(trans('ev.soc_derivation').'(%)')
//                    ->formatStateUsing(fn($state)=>Number::format($state,1))
//                    ->toggleable(),
//                Tables\Columns\TextColumn::make('soc_middle')
//                    ->label(trans('ev.soc_middle').'(%)')
//                    ->formatStateUsing(fn($state)=>Number::format($state,1))
//                    ->toggleable(),
//                Tables\Columns\TextColumn::make('daily.a_charge')
//                    ->label(trans('ev.charge'))
//                    ->formatStateUsing(fn($state)=>Number::format($state,1))
//                    ->toggleable(),
//                Tables\Columns\TextColumn::make('daily.a_discharge')
//                    ->label(trans('ev.discharge'))
//                    ->formatStateUsing(fn($state)=>Number::format($state,1))
//                    ->toggleable(),
//                Tables\Columns\TextColumn::make('voltage_spread')
//                    ->label(trans('ev.voltage_spread'))
//                    ->badge()
//                    ->color(fn(string $state) => $state<0.1?'success':($state<0.2?'warning':'danger'))
//                    ->formatStateUsing(fn($state)=>Number::format($state,3))
//                    ->toggleable(),
//                Tables\Columns\TextColumn::make('daily.consumption')
//                    ->label(trans('ev.consumption'))
//                    ->formatStateUsing(fn($state)=>Number::format($state,1))
//                    ->toggleable()
//                    ->toggledHiddenByDefault(),
//                Tables\Columns\TextColumn::make('daily.a_consumption')
//                    ->label(trans('ev.a_consumption'))
//                    ->formatStateUsing(fn($state)=>Number::format($state,1))
//                    ->toggleable()
//                    ->toggledHiddenByDefault(),
                Tables\Columns\TextColumn::make('distance')
                    ->label(trans('ev.distance'))
                    ->formatStateUsing(fn($state)=>Number::format($state,1)),



//                Tables\Columns\TextColumn::make('consumption')
//                    ->label(trans('ev.consumption'))
//                    ->default(function(Model $record){
//                        $distance = !empty($record?->parent?->odo)?$record->odo - $record?->parent?->odo:$record->odo;
//                        $capacity = $record->vehicle->capacity/100 * ($record?->parent?->soc? $record->parent->soc - $record->soc:0);
//                        return $distance>0 ? Number::format($capacity/$distance * 100,0)."kWh/100km":"";
//                    }),
//                Tables\Columns\TextColumn::make('range')
//                    ->label(trans('ev.range'))
//                    ->default(function(Model $record){
//                        $distance = !empty($record?->parent?->odo)?$record->odo - $record?->parent?->odo:$record->odo;
//                        $capacity = $record->vehicle->capacity/100 * ($record?->parent?->soc? $record->parent->soc - $record->soc:0);
//                        return $capacity>0? Number::format($distance/$capacity * 100,0)."km":"";
//                    }),
            ])
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
            ->defaultSort(fn(Builder $query)=>$query->orderBy('date','desc')->orderBy('id','desc'))
            ->actions([

                Tables\Actions\Action::make('obd_import')
                    ->visible(fn($record)=>!$record->items()->count('*'))
                    ->icon('heroicon-o-arrow-up-tray')
                    ->label(trans('ODB2'))
                    ->form([
                        FileUpload::make('obd_file')
                            ->preserveFilenames()
                            ->disk('local')
                            ->directory('obd2'),
                    ])
                    ->action(function (array $data,Model $record){
                        $csv = Reader::createFromPath(Storage::path($data['obd_file']),'r');
                        $csv->setDelimiter(';');
                        $obdFile = $data['obd_file'];
                        $obdFileArray =explode("/",$obdFile);
                        $obdFileName =end($obdFileArray);
                        $obdFileNameArray = explode(".",$obdFileName);
                        $record->update([
                            'date' => $obdFileNameArray[0],
                            'obd_file'=>$obdFile,
                        ]);
                        foreach ($csv->getRecords() as $index=>$row){

                            //if($index >=200) break;
                            $item = ObdItem::where('pid',$row[1])->first();
                            if(!empty($item) && $item->id){
                                $record->items()->firstOrCreate(['item_id'=>$item->id],['value'=>$row[2]]);
                            }
                        }
                    }),
                Tables\Actions\ViewAction::make(),
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
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListEvLogs::route('/'),
            'create' => Pages\CreateEvLog::route('/create'),
            'edit' => Pages\EditEvLog::route('/{record}/edit'),
            'view' => Pages\ViewEvLog::route('/{record}'),
        ];
    }
}
