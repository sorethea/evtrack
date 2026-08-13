<?php

namespace Modules\EV\Filament\Resources\EvLogResource\RelationManagers;

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
            ->recordTitleAttribute('log_id')
            ->columns([
                Tables\Columns\TextColumn::make('item.pid')->searchable(),
                Tables\Columns\TextColumn::make('value'),
                Tables\Columns\TextColumn::make('item.units')->label(trans('Unit'))
            ])
            ->paginated(false)
            ->defaultSort('log_id')
            ->filters([
                //
            ])
//            ->headerActions([
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
//            ])
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
