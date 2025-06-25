<?php

namespace App\Observers;

use Modules\EV\Models\EvLog;

class EvLogObserver
{
    /**
     * Handle the EvLog "created" event.
     */
    public function created(EvLog $evLog): void
    {
        $maxDate = EvLog::max('date');
        if($evLog->date >=$maxDate){
            $evLog->vehicle->soc = $evLog->soc_actual;
            $evLog->vehicle->odo = $evLog->odo;
            $evLog->vehicle->save();
        }
        $evLog->vehicle_id = auth()->user()->vehicle->id;
        $evLog->save();
    }

    /**
     * Handle the EvLog "updated" event.
     */
    public function updated(EvLog $evLog): void
    {
        $maxDate = EvLog::max('date');
        if($evLog->date >=$maxDate){
            $evLog->vehicle->soc = $evLog->soc_actual;
            $evLog->vehicle->odo = $evLog->odo;
            $evLog->vehicle->save();
        }
    }

    /**
     * Handle the EvLog "deleted" event.
     */
    public function deleted(EvLog $evLog): void
    {
        //
    }

    /**
     * Handle the EvLog "restored" event.
     */
    public function restored(EvLog $evLog): void
    {
        //
    }

    /**
     * Handle the EvLog "force deleted" event.
     */
    public function forceDeleted(EvLog $evLog): void
    {
        //
    }
}
