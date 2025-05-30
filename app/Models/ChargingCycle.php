<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ChargingCycle extends Model
{
    protected $table = 'charging_cycles_view';
    protected $guarded =[];

    public $timestamps = false;

    public $incrementing = false;
    public function children():HasMany
    {
        return $this->hasMany(EvLog::class,'cycle_id','id');
    }
}
