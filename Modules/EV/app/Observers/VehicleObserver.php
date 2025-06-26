<?php

namespace Modules\EV\Observers;

use Modules\EV\Models\Vehicle;

class VehicleObserver
{
    public function created(Vehicle $vehicle)
    {
        $vehicle->user_id = auth()->id();
        $vehicle->save();
    }
}
