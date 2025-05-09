<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Trip extends Model
{
    protected $fillable =           [
        "vehicle_id",
        "date_from",
        "date_to",
        "odo_from",
        "odo_to",
        "soc_from",
        "soc_to",
        "ac_from",
        "ac_to",
        "ad_from",
        "ad_to",
        "comment",
    ];

    protected $casts =[
        "vehicle_id"=>'integer',
        "date_from"=>'date',
        "date_to"=>'date',
        "odo_from"=>'float',
        "odo_to"=>'float',
        "soc_from"=>'float',
        "soc_to"=>'float',
        "ac_to"=>'float',
        "ac_from"=>'float',
        "ad_to"=>'float',
        "ad_from"=>'float',
        "comment",
    ];
}
