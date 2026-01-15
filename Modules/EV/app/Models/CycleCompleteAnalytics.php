<?php
namespace Modules\EV\Models;

use Illuminate\Database\Eloquent\Model;

class CycleCompleteAnalytics extends Model
{
protected $table = 'cycle_complete_analytics';

protected $primaryKey = 'cycle_id';
protected $casts = [
'cycle_start_date' => 'datetime',
'cycle_end_date' => 'datetime',
'next_start_date' => 'datetime',

// Cast all numeric fields
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
'odo_delta' => 'decimal:2',
'voltage_delta' => 'decimal:2',
'soc_delta' => 'decimal:2',
'aca_delta' => 'decimal:2',
'ada_delta' => 'decimal:2',
'ac_delta' => 'decimal:2',
'ad_delta' => 'decimal:2',
'lvc_delta' => 'decimal:2',
'hvc_delta' => 'decimal:2',
'ltc_delta' => 'decimal:2',
'htc_delta' => 'decimal:2',
'tc_delta' => 'decimal:2',
'soc_consumption_per_km' => 'decimal:4',
];

// Accessors to get item definitions
public function getStartValuesAttribute()
{
return [
'odo' => $this->start_odo,
'voltage' => $this->start_voltage,
'soc' => $this->start_soc,
'aca' => $this->start_aca,
'ada' => $this->start_ada,
'ac' => $this->start_ac,
'ad' => $this->start_ad,
'lvc' => $this->start_lvc,
'hvc' => $this->start_hvc,
'ltc' => $this->start_ltc,
'htc' => $this->start_htc,
'tc' => $this->start_tc,
];
}

public function getEndValuesAttribute()
{
return [
'odo' => $this->end_odo,
'voltage' => $this->end_voltage,
'soc' => $this->end_soc,
'aca' => $this->end_aca,
'ada' => $this->end_ada,
'ac' => $this->end_ac,
'ad' => $this->end_ad,
'lvc' => $this->end_lvc,
'hvc' => $this->end_hvc,
'ltc' => $this->end_ltc,
'htc' => $this->end_htc,
'tc' => $this->end_tc,
];
}

public function getDeltasAttribute()
{
return [
'odo' => $this->odo_delta,
'voltage' => $this->voltage_delta,
'soc' => $this->soc_delta,
'aca' => $this->aca_delta,
'ada' => $this->ada_delta,
'ac' => $this->ac_delta,
'ad' => $this->ad_delta,
'lvc' => $this->lvc_delta,
'hvc' => $this->hvc_delta,
'ltc' => $this->ltc_delta,
'htc' => $this->htc_delta,
'tc' => $this->tc_delta,
];
}
}
