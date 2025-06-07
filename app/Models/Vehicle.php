<?php

namespace App\Models;

use App\Observers\VehicleObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

#[ObservedBy([VehicleObserver::class])]
class Vehicle extends Model
{
    protected $fillable = [
        "name",
        "make",
        "model",
        "year",
        "vin",
        "plate",
        "soc",
        "odo",
        "capacity",
        "specs",
        "user_id",
        "is_default",
    ];
    protected $casts =[
        "is_default"=>'boolean',
    ];
    public function latestLog(): HasOne
    {
        return $this->hasOne(EvLog::class)->whereRaw('date=Max(date)',);
    }
    public function logs(): HasMany
    {
        return $this->hasMany(EvLog::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
