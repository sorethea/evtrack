<?php

namespace Modules\SA\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
// use Modules\SA\Database\Factories\MetricFactory;

class Metric extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = ['record_at','metadata'];
    protected $casts =[
        'metadata'=>'array',
        'record'=>'datetime',
    ];

    // protected static function newFactory(): MetricFactory
    // {
    //     // return MetricFactory::new();
    // }
}
