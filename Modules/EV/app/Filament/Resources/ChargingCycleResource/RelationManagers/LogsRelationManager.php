<?php

namespace Modules\EV\Filament\Resources\EvLogResource\RelationManagers;

use Carbon\Carbon;
use evlog;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Number;


class LogsRelationManager extends RelationManager
{
    protected static string $relationship = 'logs';

    public Model $ownerRecord;



    public function form(Schema $form): Schema
    {
        return $form
            ->schema([
                Select::make('log_id')
                    ->relationship('logs','log_id')
                    ->required(),
                TextInput::make('date')
                    ->numeric()
                    ->default(0),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('id')
            ->columns([
                Tables\Columns\TextColumn::make("date")
                    ->dateTimeTooltip()
                    ->since()
                    ->searchable(),
                Tables\Columns\TextColumn::make("duration")
                    ->getStateUsing(fn($record)=>!is_null($record&&$record?->date&&$record?->parent?->date)?gmdate("H:i",Carbon::make($record?->parent?->date??now())->diffInSeconds($record?->date??now())):0),
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
                Tables\Columns\TextColumn::make("soh")
                    ->formatStateUsing(fn(string $state): string => ($state>=100)?Number::format(100,1):Number::format($state,1))
                    ->label(trans("ev.soh")),
                Tables\Columns\ColumnGroup::make('SoC(%)',[
                    Tables\Columns\TextColumn::make('parent.soc')
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
                    Tables\Columns\TextColumn::make('detail.soc_derivation')
                        ->inverseRelationship('log')
                        ->label(trans('ev.used'))
                        ->numeric(1)
                        //->summarize(Tables\Columns\Summarizers\Sum::make()->label(trans('ev.soc_derivation')))
                        ->toggleable(),
                    Tables\Columns\TextColumn::make('consumption')
                        ->numeric(1)
                        ->formatStateUsing(fn($state)=>($state>0)?Number::format($state,1):0)
                        ->label(__('ev.consume')),
                    ])
            ])
            ->paginated(false)
            ->defaultSort('log_id')
            ->filters([
                //
            ])
            ->headerActions([
//                CreateAction::make(),
//                Action::make('obdImport')
//                    ->label('Obd Import')
//                    ->form([
//                        FileUpload::make('obd_file')
//                            ->preserveFilenames()
//                            ->disk('local')
//                            ->directory('obd2'),
//                    ])
//                    ->action(function (array $data, ) {
//                        //$evLog = EvLog::create($data);
//                        evlog::obdImportAction($data,$this->ownerRecord);
//                    })->hidden(!empty($this->ownerRecord->items->toArray())),
            ])
            ->actions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
