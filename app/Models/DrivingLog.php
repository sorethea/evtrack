<?php

namespace App\Models;

use App\Observers\DrivingLogObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Model;
#[ObservedBy([DrivingLogObserver::class])]
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
