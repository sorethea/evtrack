<?php

namespace Modules\EV\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DailyLog extends Model
{
    protected $table = 'daily_logs_view';
    protected $guarded =[];

    public $timestamps = false;

    public $incrementing = false;
    public function evLog(): BelongsTo
    {
        return $this->belongsTo(EvLog::class,'parent_id','id');
    }
}
