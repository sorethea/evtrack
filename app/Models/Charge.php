<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Charge extends Model
{
    protected $fillable = [
        "date",
        "soc_from",
        "soc_to",
        "ac_from",
        "ac_to",
        "type",
        "qty",
        "price",
    ];

    protected $casts =[
        "trip_charge"=>"boolean",
    ];
}
