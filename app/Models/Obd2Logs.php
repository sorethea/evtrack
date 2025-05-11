<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Obd2Logs extends Model
{
    protected $fillable = [
        "seconds",
        "pid",
        "value",
    ];
}
