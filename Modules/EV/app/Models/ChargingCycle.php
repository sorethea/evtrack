<?php

namespace Modules\EV\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class ChargingCycle extends Model
{
    protected $table = 'ev_logs_cycle_view';
    protected $guarded =[];

    public $timestamps = false;

    public $incrementing = false;

    public function parent():BelongsTo
    {
        return $this->belongsTo(EvLog::class,'id','id');
    }
    public function vehicle():BelongsTo
    {
        return $this->belongsTo(Vehicle::class,'vehicle_id','id');
    }

    public function logs():HasMany
    {
        return $this->hasMany(EvLogDetail::class,'cycle_id');
    }
    public function latestLog():HasOne
    {
        return $this->hasOne(EvLog::class,'cycle_id')->latest();
    }
}
