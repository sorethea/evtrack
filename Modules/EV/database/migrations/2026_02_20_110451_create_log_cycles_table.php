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
        DB::statement("CREATE OR REPLACE VIEW log_cycles AS
WITH logs_aggregated AS (
  SELECT
    l.id,
    l.cycle_id,
    l.parent_id,
    l.vehicle_id,
    l.date,
    l.log_type,
    MAX(CASE WHEN li.item_id = 1  THEN li.value END) AS odo,
    MAX(CASE WHEN li.item_id = 10 THEN li.value END) AS soc,
    MAX(CASE WHEN li.item_id = 11 THEN li.value END) AS soc_actual,
    MAX(CASE WHEN li.item_id = 19 THEN li.value END) AS ac,
    MAX(CASE WHEN li.item_id = 20 THEN li.value END) AS ad,
    MAX(CASE WHEN li.item_id = 22 THEN li.value END) AS lvc,
    MAX(CASE WHEN li.item_id = 24 THEN li.value END) AS hvc,
    MAX(CASE WHEN li.item_id = 26 THEN li.value END) AS ltc,
    MAX(CASE WHEN li.item_id = 28 THEN li.value END) AS htc,
    MAX(CASE WHEN li.item_id = 29 THEN li.value END) AS tc
  FROM ev_logs l
  LEFT JOIN ev_log_items li
    ON l.id = li.log_id AND li.item_id BETWEEN 1 AND 29
  GROUP BY l.id, l.cycle_id, l.parent_id, l.vehicle_id, l.date, l.log_type   -- added missing columns
),
-- Regen per cycle: sum of positive ac differences where log_type = 'driving'
regen_per_cycle AS (
  SELECT
    cycle_id,
    SUM(CASE
          WHEN log_type = 'driving' AND ac > prev_ac
          THEN ac - prev_ac
          ELSE 0
        END) AS regen
  FROM (
    SELECT
      cycle_id,
      log_type,
      ac,
      LAG(ac) OVER (PARTITION BY cycle_id ORDER BY date) AS prev_ac
    FROM logs_aggregated
    WHERE cycle_id IS NOT NULL
  ) with_prev
  GROUP BY cycle_id
),
-- All distinct cycles with vehicle_id and date range
cycles AS (
  SELECT
    cycle_id,
    MAX(vehicle_id) AS vehicle_id,
    MIN(date) AS start_date,
    MAX(date) AS end_date
  FROM logs_aggregated
  WHERE cycle_id IS NOT NULL
  GROUP BY cycle_id
),
-- Root of each cycle (first log by date)
root_per_cycle AS (
  SELECT DISTINCT
    cycle_id,
    FIRST_VALUE(id) OVER (PARTITION BY cycle_id ORDER BY date) AS rc_id
  FROM logs_aggregated
  WHERE cycle_id IS NOT NULL
),
-- Last child in each cycle (by date)
last_child_per_cycle AS (
  SELECT DISTINCT
    cycle_id,
    FIRST_VALUE(id) OVER (PARTITION BY cycle_id ORDER BY date DESC) AS lcc_id
  FROM logs_aggregated
  WHERE parent_id IS NOT NULL
),
-- First child of each parent (by date) – used to get the child of lcc
first_child_of_parent AS (
  SELECT DISTINCT
    parent_id,
    FIRST_VALUE(id) OVER (PARTITION BY parent_id ORDER BY date) AS clcc_id
  FROM logs_aggregated
  WHERE parent_id IS NOT NULL
)
SELECT
  c.cycle_id,
  c.vehicle_id,
  c.start_date,
  c.end_date,
  -- rc columns
  rc_row.odo      AS rc_odo,
  rc_row.soc      AS rc_soc,
  rc_row.soc_actual AS rc_soc_actual,
  rc_row.ac       AS rc_ac,
  rc_row.ad       AS rc_ad,
  rc_row.lvc      AS rc_lvc,
  rc_row.hvc      AS rc_hvc,
  rc_row.ltc      AS rc_ltc,
  rc_row.htc      AS rc_htc,
  rc_row.tc       AS rc_tc,
  -- lcc columns
  lcc_row.odo     AS lcc_odo,
  lcc_row.soc     AS lcc_soc,
  lcc_row.soc_actual AS lcc_soc_actual,
  lcc_row.ac      AS lcc_ac,
  lcc_row.ad      AS lcc_ad,
  lcc_row.lvc     AS lcc_lvc,
  lcc_row.hvc     AS lcc_hvc,
  lcc_row.ltc     AS lcc_ltc,
  lcc_row.htc     AS lcc_htc,
  lcc_row.tc      AS lcc_tc,
  -- clcc columns
  clcc_row.odo    AS clcc_odo,
  clcc_row.soc    AS clcc_soc,
  clcc_row.soc_actual AS clcc_soc_actual,
  clcc_row.ac     AS clcc_ac,
  clcc_row.ad     AS clcc_ad,
  clcc_row.lvc    AS clcc_lvc,
  clcc_row.hvc    AS clcc_hvc,
  clcc_row.ltc    AS clcc_ltc,
  clcc_row.htc    AS clcc_htc,
  clcc_row.tc     AS clcc_tc,
  -- Calculated fields
  lcc_row.ad - rc_row.ad AS discharge,
  clcc_row.ac - rc_row.ac AS charge,
  rp.regen,
  lcc_row.odo - rc_row.odo AS distance,
  -- Capacity = 100 * (discharge - regen) / (rc.soc - lcc.soc)
  100 * (lcc_row.ad - rc_row.ad - rp.regen) / NULLIF(rc_row.soc - lcc_row.soc, 0) AS capacity
FROM cycles c
LEFT JOIN root_per_cycle rc ON c.cycle_id = rc.cycle_id
LEFT JOIN logs_aggregated rc_row ON rc.rc_id = rc_row.id
LEFT JOIN last_child_per_cycle lc ON c.cycle_id = lc.cycle_id
LEFT JOIN logs_aggregated lcc_row ON lc.lcc_id = lcc_row.id
LEFT JOIN first_child_of_parent fc ON lc.lcc_id = fc.parent_id
LEFT JOIN logs_aggregated clcc_row ON fc.clcc_id = clcc_row.id
LEFT JOIN regen_per_cycle rp ON c.cycle_id = rp.cycle_id
ORDER BY c.start_date;
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        DB::statement('DROP VIEW IF EXISTS log_cycles');
    }
};
