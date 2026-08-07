<?php

namespace Modules\EV\Filament\Resources\LogPivotResource\Pages;

use Modules\EV\Filament\Resources\LogPivotResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditLogPivot extends EditRecord
{
    protected static string $resource = LogPivotResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
