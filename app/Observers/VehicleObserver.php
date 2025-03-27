<?php

namespace App\Observers;

use App\Models\User;
use App\Models\Vehicle;

class VehicleObserver
{
    public function created(Vehicle $vehicle)
    {
        $vehicle->user_id = auth()->id();
        $vehicle->save();
    }
}
