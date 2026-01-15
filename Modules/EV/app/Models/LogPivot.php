<?php

namespace Modules\EV\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
// use Modules\EV\Database\Factories\LogPivotFactory;

class LogPivot extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $table = 'ev_log_pivot';
    protected $casts = [
        'date' => 'datetime',
        'odo' => 'double',
        'voltage' => 'double',
        'soc' => 'double',
        'aca' => 'double',
        'ada' => 'double',
        'ac' => 'double',
        'ad' => 'double',
        'lvc' => 'double',
        'hvc' => 'double',
        'ltc' => 'double',
        'htc' => 'double',
        'tc' => 'double',
    ];

}
