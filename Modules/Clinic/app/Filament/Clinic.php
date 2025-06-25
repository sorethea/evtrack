<?php

namespace Modules\Clinic\Filament;

use Coolsam\Modules\Concerns\ModuleFilamentPlugin;
use Filament\Contracts\Plugin;
use Filament\Panel;

class Clinic implements Plugin
{
    use ModuleFilamentPlugin;

    public function getModuleName(): string
    {
        return 'Clinic';
    }

    public function getId(): string
    {
        return 'clinic';
    }

    public function boot(Panel $panel): void
    {
        // TODO: Implement boot() method.
    }
}
