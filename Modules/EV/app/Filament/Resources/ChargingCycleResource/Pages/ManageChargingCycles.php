<?php

namespace Modules\EV\Filament\Resources\ChargingCycleResource\Pages;

use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;
use Modules\EV\Filament\Resources\ChargingCycleResource;

class ManageChargingCycles extends ManageRecords
{
    protected static string $resource = ChargingCycleResource::class;

    protected function getHeaderActions(): array
    {

        return [
            Actions\CreateAction::make()->hidden(),
        ];
    }
}
