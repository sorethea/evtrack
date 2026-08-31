<?php

namespace Modules\SA\Filament;

use Coolsam\Modules\Concerns\ModuleFilamentPlugin;
use Filament\Contracts\Plugin;
use Filament\Panel;

class SAPlugin implements Plugin
{
    use ModuleFilamentPlugin;

    public function getModuleName(): string
    {
        return 'SA';
    }

    public function getId(): string
    {
        return 'sa';
    }

    public function boot(Panel $panel): void
    {
        // TODO: Implement boot() method.
    }
}
