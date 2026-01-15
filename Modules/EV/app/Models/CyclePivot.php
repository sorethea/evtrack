<?php

namespace Modules\EV\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
// use Modules\EV\Database\Factories\CyclePivotFactory;

class CyclePivot extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $table = 'cycle_pivot';
    protected $casts = [
        'cycle_start_date' => 'datetime',
        'cycle_end_date' => 'datetime',
        'next_cycle_start_date' => 'datetime',
        'start_odo' => 'decimal:2',
        'end_odo' => 'decimal:2',
        'distance_km' => 'decimal:2',
        'start_soc' => 'decimal:2',
        'end_soc' => 'decimal:2',
        'soc_change' => 'decimal:2',
        'soc_consumption_per_km' => 'decimal:2',
        'total_logs' => 'integer',
    ];

    public function vehicle()
    {
        return $this->belongsTo(Vehicle::class);
    }

    // Get the parent log
    public function parentLog()
    {
        return $this->belongsTo(EvLog::class, 'parent_id');
    }

    // Get the next cycle
    public function nextCycle()
    {
        return $this->belongsTo(self::class, 'next_cycle_id', 'cycle_id');
    }
}
