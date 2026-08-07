<?php

namespace Modules\EV5\Filament;

use Coolsam\Modules\Concerns\ModuleFilamentPlugin;
use Filament\Contracts\Plugin;
use Filament\Panel;

class EV5Plugin implements Plugin
{
    use ModuleFilamentPlugin;

    public function getModuleName(): string
    {
        return 'EV5';
    }

    public function getId(): string
    {
        return 'ev5';
    }

    public function boot(Panel $panel): void
    {
        // TODO: Implement boot() method.
    }
}
