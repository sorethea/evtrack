<?php

namespace App\Models;

use App\Observers\EvLogObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\HasOne;

#[ObservedBy([EvLogObserver::class])]
class EvLog extends Model
{
    protected $fillable =[
        "parent_id",
        "vehicle_id",
        "cycle_id",
        "date",
       // "odo",
        "log_type",
//        "soc_from",
//        "soc_to",
//        "soc_actual",
//        "ac",
//        "ad",
//        "ac_power",
//        "ad_power",
//        "highest_volt_cell",
//        "lowest_volt_cell",
//        "highest_temp_cell",
//        "lowest_temp_cell",
//        "voltage",
        "charge_type",
        "obd_file",
        "remark",
    ];
    protected $appends=[
//            'soc_from',
//            'soc_to',
//            'soc_derivation',
//            'soc_middle',
//            'distance',
            //'voltage_spread',
        ];
//
//    public function getVoltageSpreadAttribute()
//    {
//        return $this?->items?->where('item_id',24)->value('value')-$this?->items?->where('item_id',22)->value('value');
//    }
//    protected function socFrom(): Attribute
//    {
//        return new Attribute(
//            get: fn()=>$this?->parent?->items?->where('item_id',11)->value('value'),set: null
//        );
//    }
//    protected function socTo(): Attribute
//    {
//        return new Attribute(
//            get: fn()=>$this?->items?->where('item_id',11)->value('value'),set: null
//        );
//    }
//    public function getSocToAttribute()
//    {
//        return $this?->items?->where('item_id',11)->value('value');
//    }
//    public function getSocFromAttribute()
//    {
//        return $this?->parent?->items?->where('item_id',11)->value('value');
//    }
//    public function getDistanceAttribute()
//    {
//        return $this?->items?->where('item_id',1)->value('value')-$this?->parent?->items?->where('item_id',1)->value('value');
//    }
//    public function getSocDerivationAttribute()
//    {
//        return $this?->soc_from - $this->soc_to;
//    }
//    public function getSocMiddleAttribute()
//    {
//        return $this?->soc_to - 100*($this?->items?->where('item_id',19)->value('value') - $this?->items?->where('item_id',20)->value('value'))/$this->vehicle->capacity;
//    }
    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class,'parent_id');
    }
    public function items(): HasManyThrough
    {
        return $this->hasManyThrough(Item::class,'log_item','log_id','item_id');
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
