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
          l.id,
          l.parent_id,
          l.cycle_id,
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
        GROUP BY l.id, l.date)
        SELECT
          c.id,
          c.odo,
          c.soc,
          c.ac,
          c.ad,
          c.lvc,
          c.hvc,
          c.ltc,
          c.htc,
          c.tc,
          c.odo - p.odo as distance,
          FROM ev_logs_base c
          LEFT JOIN ev_logs_base p ON c.parent_id = p.id;
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
