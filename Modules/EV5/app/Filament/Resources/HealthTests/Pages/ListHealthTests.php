<?php

namespace Modules\EV5\Filament\Resources\HealthTests\Pages;

use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Modules\EV5\Filament\Resources\HealthTests\HealthTestResource;

class ListHealthTests extends ListRecords
{
    protected static string $resource = HealthTestResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
