<?php

namespace Modules\EV\Filament\Resources\EvLogResource\Pages;

use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Modules\EV\Filament\Resources\EvLogResource;

class EditEvLog extends EditRecord
{
    protected static string $resource = EvLogResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
