<?php

namespace Modules\EV\Facades;

use Illuminate\Support\Facades\Facade;

class EvLog extends Facade
{
    protected static function getFacadeAccessor():string
    {
        return 'evlog';
    }
}
