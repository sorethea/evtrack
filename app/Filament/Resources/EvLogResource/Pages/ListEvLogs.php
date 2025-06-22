<?php

namespace App\Filament\Resources\EvLogResource\Pages;

use App\Filament\Resources\EvLogResource;
use App\Models\EvLog;
use App\Models\EvLogItem;
use App\Models\Obd2Logs;
use App\Models\ObdItem;
use Filament\Actions;
use Filament\Forms\Components\Fieldset;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Get;
use Filament\Pages\Concerns\ExposesTableToWidgets;
use Filament\Resources\Pages\ListRecords;
use Filament\Tables\Columns\Concerns\InteractsWithTableQuery;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Storage;
use League\Csv\Reader;

class ListEvLogs extends ListRecords
{
    use ExposesTableToWidgets;

    protected static string $resource = EvLogResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
            Actions\Action::make('obdImport')
                ->label('Obd Import')
                ->form(\evlog::obdImportForm())
                ->action(function (array $data){
                    $evLog = EvLog::create($data);
                    \evlog::obdImportAction($data,$evLog);
                }),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            //EvLogResource\Widgets\EvLogOverview::class,
            EvLogResource\Widgets\ChargingCycleOverview::make(),
        ];
    }
}
