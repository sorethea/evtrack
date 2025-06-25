<?php

namespace Modules\Clinic\Providers;

use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;

class ClinicPanelProvider extends PanelProvider
{

    #[\Override] public function panel(Panel $panel): Panel
    {
        return $panel->id('clinic')
            ->path('clinic')
            ->colors([
                'primary'=>Color::Green,
            ]);
    }
}
