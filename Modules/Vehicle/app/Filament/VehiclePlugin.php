<?php

namespace Modules\Vehicle\Filament;

use Coolsam\Modules\Concerns\ModuleFilamentPlugin;
use Filament\Contracts\Plugin;
use Filament\Panel;

class VehiclePlugin implements Plugin
{
    use ModuleFilamentPlugin;

    public function getModuleName(): string
    {
        return 'Vehicle';
    }

    public function getId(): string
    {
        return 'vehicle';
    }

    public function boot(Panel $panel): void
    {
        // TODO: Implement boot() method.
    }
}
