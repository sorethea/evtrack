<?php

namespace App\Observers;

use App\Models\EvLog;

class EvLogObserver
{
    /**
     * Handle the EvLog "created" event.
     */
    public function created(EvLog $evLog): void
    {
//        $evLog->parent_id = $evLog->id -1;
//        $evLog->save();
    }

    /**
     * Handle the EvLog "updated" event.
     */
    public function updated(EvLog $evLog): void
    {
        //
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
