<?php

namespace App\Observers;

use App\Models\DrivingLog;
use Modules\EV\Models\Vehicle;

class DrivingLogObserver
{
    /**
     * Handle the DrivingLog "created" event.
     */
    public function created(DrivingLog $drivingLog): void
    {
        $vehicle = Vehicle::where('user_id',auth()->user()->id)->where('is_default',true)->first();
        if($drivingLog->odo>$vehicle->odo){
            $vehicle->odo = $drivingLog->odo;
            $vehicle->soc = $drivingLog->soc_to;
            $vehicle->save();
        }

    }

    /**
     * Handle the DrivingLog "updated" event.
     */
    public function updated(DrivingLog $drivingLog): void
    {

    }

    /**
     * Handle the DrivingLog "deleted" event.
     */
    public function deleted(DrivingLog $drivingLog): void
    {
        //
    }

    /**
     * Handle the DrivingLog "restored" event.
     */
    public function restored(DrivingLog $drivingLog): void
    {
        //
    }

    /**
     * Handle the DrivingLog "force deleted" event.
     */
    public function forceDeleted(DrivingLog $drivingLog): void
    {
        //
    }
}
