<?php

namespace App\Observers;

use Modules\EV\Models\Vehicle;

class VehicleObserver
{
    public function created(Vehicle $vehicle)
    {
        $vehicle->user_id = auth()->id();
        $vehicle->save();
    }
}
