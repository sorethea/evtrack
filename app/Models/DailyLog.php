<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DailyLog extends Model
{
    protected $table = 'daily_logs_view';
    protected $guarded =[];

    public $timestamps = false;

    public $incrementing = false;
    public function log(): BelongsTo
    {
        return $this->belongsTo(EvLog::class,'id','id');
    }
}
