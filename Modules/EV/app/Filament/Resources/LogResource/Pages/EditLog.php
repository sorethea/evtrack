<?php

namespace Modules\EV\Filament\Resources\LogResource\Pages;

use Modules\EV\Filament\Resources\LogResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditLog extends EditRecord
{
    protected static string $resource = LogResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
