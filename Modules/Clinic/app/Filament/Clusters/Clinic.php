<?php

namespace Modules\Clinic\Filament\Clusters;

use Filament\Clusters\Cluster;
use Nwidart\Modules\Facades\Module;

class Clinic extends Cluster
{
    public static function getModuleName(): string
    {
        return 'Clinic';
    }

    public static function getModule(): \Nwidart\Modules\Module
    {
        return Module::findOrFail(static::getModuleName());
    }

    public static function getNavigationLabel(): string
    {
        return __('Clinic');
    }

    protected static ?string $navigationGroup = 'Clinic';

    public static function getNavigationIcon(): ?string
    {
        return 'heroicon-o-squares-2x2';
    }
}
