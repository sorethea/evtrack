<?php

namespace Modules\EV\Filament\Resources\ChargingCycleResource\Pages;

use Filament\Resources\Pages\ViewRecord;
use Modules\EV\Filament\Resources\ChargingCycleResource;

class ViewChargingCycle extends ViewRecord
{
    protected static string $resource = ChargingCycleResource::class;

    protected function getHeaderWidgets(): array
    {
        return [
            ChargingCycleResource\Widgets\ChargingCycleOverview::make([
                'record'=>$this->record,
            ]),
        ];
    }
}
