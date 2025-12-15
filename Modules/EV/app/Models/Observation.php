<?php

namespace Modules\EV\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
// use Modules\EV\Database\Factories\ObservationFactory;

class Observation extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [];

    // protected static function newFactory(): ObservationFactory
    // {
    //     // return ObservationFactory::new();
    // }
}
