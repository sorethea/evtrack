<?php

namespace Modules\EV\Filament;

use Coolsam\Modules\Concerns\ModuleFilamentPlugin;
use Filament\Contracts\Plugin;
use Filament\Panel;

class EVPlugin implements Plugin
{
    use ModuleFilamentPlugin;

    public function getModuleName(): string
    {
        return 'EV';
    }

    public function getId(): string
    {
        return 'ev';
    }

    public function boot(Panel $panel): void
    {
        // TODO: Implement boot() method.
    }
}
