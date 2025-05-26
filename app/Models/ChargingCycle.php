<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ChargingCycle extends Model
{
    protected $table = 'charging_cycles_view';
    protected $guarded =[];

    public $timestamps = false;

    public $incrementing = false;
}
