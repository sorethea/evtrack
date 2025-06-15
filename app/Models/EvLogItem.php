<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EvLogItem extends Model
{
    protected $fillable = [
        "log_id",
        "item_id",
        "value",
    ];


    public function log():BelongsTo
    {
        return $this->belongsTo(EvLog::class,'log_id');
    }

    public function item():BelongsTo
    {
        return $this->belongsTo(ObdItem::class,'item_id');
    }
}
