<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DrivingLog extends Model
{
    protected $fillable =[
        "date",
        "odo",
        "type",
        "soc_from",
        "soc_to",
        "ac",
        "ad",
        "voltage",
        "remark",
    ];
}
