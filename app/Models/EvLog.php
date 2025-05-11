<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

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
}
