<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DailyLog extends Model
{
    public function log(): BelongsTo
    {
        return $this->belongsTo(EvLog::class,'id','id');
    }
}
