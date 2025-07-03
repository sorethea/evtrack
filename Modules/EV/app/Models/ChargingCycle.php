<?php

namespace Modules\EV\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ChargingCycle extends Model
{
    protected $table = 'ev_logs_cycle_view';
    protected $guarded =[];

    public $timestamps = false;

    public $incrementing = false;
}
