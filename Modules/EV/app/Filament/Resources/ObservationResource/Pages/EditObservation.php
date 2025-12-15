<?php

namespace Modules\EV\Filament\Resources\ObservationResource\Pages;

use Modules\EV\Filament\Resources\ObservationResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditObservation extends EditRecord
{
    protected static string $resource = ObservationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
