<?php

namespace App\Helpers;

use Illuminate\Database\Eloquent\Model;

class EvLog
{
    function getItemValue(Model $evLog, int $item_id):float {
        return $evLog->items->where('item_id',$item_id)->value('value');
    }
}
