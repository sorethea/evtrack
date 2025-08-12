<?php

namespace Modules\EV\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EvLogDetail extends Model
{
    protected $table = 'ev_logs_view';
    protected $casts =['data'=>'datetime'];

    public $timestamps = false;

    public $incrementing = false;

    public function log() :BelongsTo
    {
        return $this->belongsTo(EvLog::class,'log_id');
    }
    public function cycle(): BelongsTo
    {
        return  $this->belongsTo(ChargingCycle::class,'cycle_id');
    }
}
