<?php

namespace Modules\EV\Models;

use Illuminate\Database\Eloquent\Model;

class ObdItem extends Model
{
    protected $fillable = [
        "pid",
        "name",
        "units",
    ];
}
