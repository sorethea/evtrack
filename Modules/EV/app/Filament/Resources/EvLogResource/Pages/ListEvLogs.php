<?php

namespace Modules\EV\Filament\Resources\EvLogResource\Pages;

use Filament\Actions;
use Filament\Pages\Concerns\ExposesTableToWidgets;
use Filament\Resources\Pages\ListRecords;
use Modules\EV\Filament\Resources\EvLogResource;
use Modules\EV\Models\EvLog;

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
            \Modules\EV\Filament\Resources\EvLogResource\Widgets\ChargingCycleOverview::make(),
        ];
    }
}
