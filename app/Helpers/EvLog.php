<?php

namespace App\Helpers;

class EvLog
{
    public static function getItemValue(\App\Models\EvLog $evLog, int $item_id):float {
        return $evLog->items->where('item_id',$item_id)->value('value')??0;
    }
    public static function getParentItemValue(\App\Models\EvLog $evLog, int $item_id):float {
        return $evLog->parent->items->where('item_id',$item_id)->value('value')??0;
    }
    public static function getCycleItemValue(\App\Models\EvLog $evLog, int $item_id):float {
        return $evLog->cycle->items->where('item_id',$item_id)->value('value')??0;
    }
    public static function getDistance(\App\Models\EvLog $evLog):float
    {
        return (self::getItemValue($evLog,1) - self::getParentItemValue($evLog,1))??0;
    }
}
