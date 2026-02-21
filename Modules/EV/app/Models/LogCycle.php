<?php

namespace Modules\EV\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
// use Modules\EV\Database\Factories\LogCycleFactory;

class LogCycle extends Model
{
    use HasFactory;


    protected $primaryKey = "cycle_id";

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [];

    // protected static function newFactory(): LogCycleFactory
    // {
    //     // return LogCycleFactory::new();
    // }
}
