<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::statement('CREATE VIEW ev_logs_view AS
        WITH ev_logs_base AS(
        SELECT
          l.id as log_id,
          l.vehicle_id,
          l.parent_id,
          l.date,
          MAX(CASE WHEN li.item_id = 1 THEN li.value END) AS odo,
          MAX(CASE WHEN li.item_id = 11 THEN li.value END) AS soc,
          MAX(CASE WHEN li.item_id = 19 THEN li.value END) AS ac,
          MAX(CASE WHEN li.item_id = 20 THEN li.value END) AS ad,
          MAX(CASE WHEN li.item_id = 22 THEN li.value END) AS lvc,
          MAX(CASE WHEN li.item_id = 24 THEN li.value END) AS hvc,
          MAX(CASE WHEN li.item_id = 26 THEN li.value END) AS ltc,
          MAX(CASE WHEN li.item_id = 28 THEN li.value END) AS htc,
          MAX(CASE WHEN li.item_id = 29 THEN li.value END) AS tc
        FROM ev_logs l
        LEFT JOIN ev_log_items li
            ON l.id = li.log_id
            AND li.item_id BETWEEN 1 AND 29
        GROUP BY l.id,l.parent_id,l.vehicle_id, l.date)
        SELECT
          c.log_id,
          c.date,
          c.odo,
          c.soc,
          c.ac,
          c.ad,
          c.lvc,
          c.hvc,
          c.ltc,
          c.htc,
          c.tc,
          p.soc - c.soc AS soc_derivation,
          c.hvc - c.lvc AS v_spread,
          c.htc - c.ltc AS t_spread,
          c.soc -100*(c.ac-c.ad)/v.capacity AS soc_middle,
          c.ac - p.ac AS charge,
          c.ad - p.ad AS discharge,
          c.odo - p.odo AS distance,
          100*( c.ad - p.ad -(c.ac - p.ac))/(c.odo - p.odo) AS a_consumption,
          v.capacity*(p.soc - c.soc)/(c.odo - p.odo) AS consumption
          FROM ev_logs_base c
          LEFT JOIN ev_logs_base p ON c.parent_id = p.log_id
          LEFT JOIN vehicles v ON c.vehicle_id =v.id;
        ');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement('DROP VIEW IF EXISTS ev_logs_view');
    }
};
