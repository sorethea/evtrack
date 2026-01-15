<?php

namespace Modules\EV\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
// use Modules\EV\Database\Factories\CyclePivotFactory;

class CyclePivot extends Model
{
    use HasFactory;

    protected $table = 'cycle_complete_analytics';

    // Set default values for attributes
    protected $attributes = [
        'start_odo' => 0,
        'start_voltage' => 0,
        'start_soc' => 0,
        'start_aca' => 0,
        'start_ada' => 0,
        'start_ac' => 0,
        'start_ad' => 0,
        'start_lvc' => 0,
        'start_hvc' => 0,
        'start_ltc' => 0,
        'start_htc' => 0,
        'start_tc' => 0,

        'end_odo' => 0,
        'end_voltage' => 0,
        'end_soc' => 0,
        'end_aca' => 0,
        'end_ada' => 0,
        'end_ac' => 0,
        'end_ad' => 0,
        'end_lvc' => 0,
        'end_hvc' => 0,
        'end_ltc' => 0,
        'end_htc' => 0,
        'end_tc' => 0,

        'next_start_odo' => 0,
        'next_start_voltage' => 0,
        'next_cycle_soc' => 0,
        'next_start_aca' => 0,
        'next_start_ada' => 0,
        'next_start_ac' => 0,
        'next_start_ad' => 0,
        'next_start_lvc' => 0,
        'next_start_hvc' => 0,
        'next_start_ltc' => 0,
        'next_start_htc' => 0,
        'next_start_tc' => 0,

        'distance_km' => 0,
        'ac_delta' => 0,
        'soc_consumption_per_km' => 0,
    ];

    protected $casts = [
        'cycle_start_date' => 'datetime',
        'cycle_end_date' => 'datetime',
        'next_start_date' => 'datetime',

        // Cast all numeric fields with defaults
        'start_odo' => 'decimal:2',
        'start_voltage' => 'decimal:2',
        'start_soc' => 'decimal:2',
        'start_aca' => 'decimal:2',
        'start_ada' => 'decimal:2',
        'start_ac' => 'decimal:2',
        'start_ad' => 'decimal:2',
        'start_lvc' => 'decimal:2',
        'start_hvc' => 'decimal:2',
        'start_ltc' => 'decimal:2',
        'start_htc' => 'decimal:2',
        'start_tc' => 'decimal:2',

        'end_odo' => 'decimal:2',
        'end_voltage' => 'decimal:2',
        'end_soc' => 'decimal:2',
        'end_aca' => 'decimal:2',
        'end_ada' => 'decimal:2',
        'end_ac' => 'decimal:2',
        'end_ad' => 'decimal:2',
        'end_lvc' => 'decimal:2',
        'end_hvc' => 'decimal:2',
        'end_ltc' => 'decimal:2',
        'end_htc' => 'decimal:2',
        'end_tc' => 'decimal:2',

        'next_start_odo' => 'decimal:2',
        'next_start_voltage' => 'decimal:2',
        'next_cycle_soc' => 'decimal:2',
        'next_start_aca' => 'decimal:2',
        'next_start_ada' => 'decimal:2',
        'next_start_ac' => 'decimal:2',
        'next_start_ad' => 'decimal:2',
        'next_start_lvc' => 'decimal:2',
        'next_start_hvc' => 'decimal:2',
        'next_start_ltc' => 'decimal:2',
        'next_start_htc' => 'decimal:2',
        'next_start_tc' => 'decimal:2',

        'distance_km' => 'decimal:2',
        'current_cycle_ac_delta' => 'decimal:2',
        'ac_delta' => 'decimal:2',
        'soc_consumption_per_km' => 'decimal:4',
    ];

    // Accessor to always return 0 if NULL
    public function getNextStartAcAttribute($value)
    {
        return $value ?? 0;
    }

    public function getNextCycleSocAttribute($value)
    {
        return $value ?? 0;
    }

    // Similarly for all other next_* fields
    public function getNextStartOdoAttribute($value) { return $value ?? 0; }
    public function getNextStartVoltageAttribute($value) { return $value ?? 0; }
    public function getNextStartAcaAttribute($value) { return $value ?? 0; }
    public function getNextStartAdaAttribute($value) { return $value ?? 0; }
    public function getNextStartAdAttribute($value) { return $value ?? 0; }
    public function getNextStartLvcAttribute($value) { return $value ?? 0; }
    public function getNextStartHvcAttribute($value) { return $value ?? 0; }
    public function getNextStartLtcAttribute($value) { return $value ?? 0; }
    public function getNextStartHtcAttribute($value) { return $value ?? 0; }
    public function getNextStartTcAttribute($value) { return $value ?? 0; }
}
