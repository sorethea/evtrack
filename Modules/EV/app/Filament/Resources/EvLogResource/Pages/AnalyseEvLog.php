<?php

namespace Modules\EV\Filament\Resources\EvLogResource\Pages;

use Modules\EV\Filament\Resources\EvLogResource;
use Filament\Resources\Pages\Page;

class AnalyseEvLog extends Page
{
    protected static string $resource = EvLogResource::class;

    protected static string $view = 'ev::filament.resources.ev-log-resource.pages.analyse-ev-log';

    protected static ?string $title = 'Analyse';
}
