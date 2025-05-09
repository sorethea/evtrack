<?php

namespace App\Models;

use App\Observers\VehicleObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
#[ObservedBy([VehicleObserver::class])]
class Vehicle extends Model
{
    protected $fillable = [
        "name",
        "make",
        "model",
        "year",
        "vin",
        "soc",
        "odo",
        "specs",
        "user_id",
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
