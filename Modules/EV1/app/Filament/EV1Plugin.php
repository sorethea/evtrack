<?php

namespace Modules\EV1\Filament;

use Coolsam\Modules\Concerns\ModuleFilamentPlugin;
use Filament\Contracts\Plugin;
use Filament\Panel;

class EV1Plugin implements Plugin
{
    use ModuleFilamentPlugin;

    public function getModuleName(): string
    {
        return 'EV1';
    }

    public function getId(): string
    {
        return 'ev1';
    }

    public function boot(Panel $panel): void
    {
        // TODO: Implement boot() method.
    }
}
