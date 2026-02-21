<?php

namespace Modules\EV\Filament\Resources\LogCycleResource\Pages;

use Modules\EV\Filament\Resources\LogCycleResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListLogCycles extends ListRecords
{
    protected static string $resource = LogCycleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
