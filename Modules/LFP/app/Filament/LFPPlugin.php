<?php

namespace Modules\LFP\Filament;

use Coolsam\Modules\Concerns\ModuleFilamentPlugin;
use Filament\Contracts\Plugin;
use Filament\Panel;

class LFPPlugin implements Plugin
{
    use ModuleFilamentPlugin;

    public function getModuleName(): string
    {
        return 'LFP';
    }

    public function getId(): string
    {
        return 'lfp';
    }

    public function boot(Panel $panel): void
    {
        // TODO: Implement boot() method.
    }
}
