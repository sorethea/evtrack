<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Charge extends Model
{
    protected $fillable = [
        "trip_id",
        "date",
        "soc_from",
        "soc_to",
        "ac_from",
        "ac_to",
        "type",
        "trip_charge",
        "price",
    ];

    protected $casts =[
        "trip_charge"=>"boolean",
    ];
}
