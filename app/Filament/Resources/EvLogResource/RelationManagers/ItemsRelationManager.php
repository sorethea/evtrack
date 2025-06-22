<?php

namespace App\Filament\Resources\EvLogResource\RelationManagers;

use App\Models\EvLog;
use Filament\Forms;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use PhpParser\Node\Expr\AssignOp\Mod;

class ItemsRelationManager extends RelationManager
{
    protected static string $relationship = 'items';


    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('item_id')
                    ->relationship('item','pid')
                    ->required(),
                Forms\Components\TextInput::make('value')
                    ->numeric()
                    ->default(0),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('item_id')
            ->columns([
                Tables\Columns\TextColumn::make('item.pid')->searchable(),
                Tables\Columns\TextColumn::make('value'),
                Tables\Columns\TextColumn::make('item.units')->label(trans('Unit'))
            ])
            ->paginated(false)
            ->defaultSort('item_id')
            ->filters([
                //
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make(),
                Tables\Actions\Action::make('obdImport')
                    ->label('Obd Import')
                    ->form([
                        FileUpload::make('obd_file')
                            ->preserveFilenames()
                            ->disk('local')
                            ->directory('obd2'),
                    ])
                    ->action(function (array $data, Model $record) {
                        //$evLog = EvLog::create($data);
                        \evlog::obdImportAction($data,$record);
                    })
                    ->hidden(fn($record)=>empty($record->items)),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }
}
