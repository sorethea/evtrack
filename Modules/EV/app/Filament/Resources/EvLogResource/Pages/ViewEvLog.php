<?php

namespace Modules\EV\Filament\Resources\EvLogResource\Pages;

use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Modules\EV\Filament\Resources\EvLogResource;

class ViewEvLog extends ViewRecord
{
    protected static string $resource = EvLogResource::class;
    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
    protected function getHeaderWidgets(): array
    {
        return [
            EvLogResource\Widgets\ChargingCycleOverview::make([
                $this->record,
            ]),
        ];
    }

}
