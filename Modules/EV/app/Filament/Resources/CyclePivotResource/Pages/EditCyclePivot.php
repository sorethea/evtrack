<?php

namespace Modules\EV\Filament\Resources\CyclePivotResource\Pages;

use Modules\EV\Filament\Resources\CyclePivotResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditCyclePivot extends EditRecord
{
    protected static string $resource = CyclePivotResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
