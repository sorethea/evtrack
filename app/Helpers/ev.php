<?php
if(!function_exists('get_log_item_value')){
    function (\App\Models\EvLog $evLog, int $item_id) {
        return $evLog->items->where('item_id',$item_id)->value('value');
    };
}
