<?php

namespace Modules\EV\Filament\Resources\EvLogResource\Pages;

use Filament\Resources\Pages\ViewRecord;
use Modules\EV\Filament\Resources\EvLogResource;
use Filament\Resources\Pages\Page;

class AnalyseEvLog extends ViewRecord
{
    protected static string $resource = EvLogResource::class;

    protected static string $view = 'ev::filament.resources.ev-log-resource.pages.analyse-ev-log';

    protected static ?string $title = 'Analyse';

    protected function getHeaderWidgets(): array
    {
        return [
            EvLogResource\Widgets\AnalyseEvLogOverview::make([
                'record'=>$this->record,
            ])

        ];
    }
}
