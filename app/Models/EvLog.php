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
        "vehicle_id",
        "date",
        "odo",
        "log_type",
        "soc",
        "soc_actual",
        "ac",
        "ad",
        "ac_power",
        "ad_power",
        "highest_volt_cell",
        "lowest_volt_cell",
        "highest_temp_cell",
        "lowest_temp_cell",
        "voltage",
        "charge_type",
        "remark",
    ];

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class,'parent_id');
    }
    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class,'vehicle_id');
    }
}
