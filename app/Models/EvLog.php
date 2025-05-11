<?php

namespace App\Models;

use App\Observers\EvLogObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[ObservedBy([EvLogObserver::class])]
class EvLog extends Model
{
    protected $fillable =[
        "parent_id",
        "date",
        "seconds",
        "odo",
        "type",
        "soc",
        "ac",
        "ad",
        "voltage",
        "remark",
    ];

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class,'parent_id');
    }
}
