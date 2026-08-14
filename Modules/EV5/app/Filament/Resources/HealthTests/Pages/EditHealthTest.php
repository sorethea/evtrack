<?php

namespace Modules\EV5\Filament\Resources\HealthTests\Pages;

use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Modules\EV5\Filament\Resources\HealthTests\HealthTestResource;

class EditHealthTest extends EditRecord
{
    protected static string $resource = HealthTestResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
