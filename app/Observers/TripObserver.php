<?php

namespace App\Observers;

use App\Models\Trip;


class TripObserver
{
    function created(Trip $trip)
    {
        $trip->vehicle_id = auth()->user()->vehicle->id;
        $trip->save();
    }
}
