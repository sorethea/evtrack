<?php

namespace App\Models;

use App\Observers\EvLogObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

#[ObservedBy([EvLogObserver::class])]
class EvLog extends Model
{
    protected $fillable =[
        "parent_id",
        "vehicle_id",
        "cycle_id",
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
        "obd_file",
        "remark",
    ];
    protected $appends=[
            'soc_from',
            'soc_to',
        ];

    public function getSocToAttribute()
    {
        return $this?->items()?->where('item_id',10)->value('value');
    }
    public function getSocFromAttribute()
    {
        return $this?->parent()?->items()?->where('item_id',10)->value('value');
    }
    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class,'parent_id');
    }
    public function items(): HasMany
    {
        return $this->hasMany(EvLogItem::class,'log_id','id');
    }
    public function daily(): HasOne
    {
        return $this->hasOne(DailyLog::class,'parent_id','id');
    }
    public function cycle(): BelongsTo
    {
        return $this->belongsTo(self::class,'cycle_id')->where("log_type","charging");
    }
    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'cycle_id')->where('log_type','driving');
    }
    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class,'vehicle_id');
    }
}
