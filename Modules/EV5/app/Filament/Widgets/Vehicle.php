<?php

namespace Modules\EV5\Filament\Widgets;

use Filament\Widgets\Widget;
use Illuminate\Database\Eloquent\Model;

class Vehicle extends Widget

{

    protected string $view = 'ev5::filament.widgets.vehicle';
    public string $header = "My Vehicle";
    public object $vehicle;
    public function mount(): void
    {
        $this->vehicle = auth()->user()->vehicle->where('is_default',true)->first();
    }

}
