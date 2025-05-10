<?php

namespace App\Filament\Resources\ChargeResource\Pages;

use App\Filament\Resources\ChargeResource;
use App\Filament\Resources\ChargeResource\Widgets\ChargeCost;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Contracts\View\View;

class ListCharges extends ListRecords
{
    protected static string $resource = ChargeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }

    public function getHeader(): ?View
    {
        return "This is header";
    }

}
