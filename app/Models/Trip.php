<?php

namespace App\Models;

use App\Observers\TripObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\EV\Models\Vehicle;

#[ObservedBy(TripObserver::class)]
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

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class,'vehicle_id');
    }
}
