<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
// Migration to create view
        DB::statement("
    CREATE OR REPLACE VIEW ev_log_pivot AS
    SELECT
        el.id,
        el.parent_id,
        el.vehicle_id,
        el.cycle_id,
        el.log_type,
        el.date,
        MAX(CASE WHEN li.item_id = 1 THEN li.value END) AS odo,
        MAX(CASE WHEN li.item_id = 2 THEN li.value END) AS voltage,
        MAX(CASE WHEN li.item_id = 11 THEN li.value END) AS soc,
        MAX(CASE WHEN li.item_id = 17 THEN li.value END) AS aca,
        MAX(CASE WHEN li.item_id = 18 THEN li.value END) AS ada,
        MAX(CASE WHEN li.item_id = 19 THEN li.value END) AS ac,
        MAX(CASE WHEN li.item_id = 20 THEN li.value END) AS ad,
        MAX(CASE WHEN li.item_id = 22 THEN li.value END) AS lvc,
        MAX(CASE WHEN li.item_id = 24 THEN li.value END) AS hvc,
        MAX(CASE WHEN li.item_id = 26 THEN li.value END) AS ltc,
        MAX(CASE WHEN li.item_id = 28 THEN li.value END) AS htc,
        MAX(CASE WHEN li.item_id = 29 THEN li.value END) AS tc
    FROM ev_logs el
    LEFT JOIN ev_log_items li ON el.id = li.log_id
    GROUP BY el.id, el.parent_id, el.vehicle_id, el.cycle_id, el.log_type, el.date
");
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        DB::statement('DROP VIEW IF EXISTS ev_logs_pivot');
    }
};
