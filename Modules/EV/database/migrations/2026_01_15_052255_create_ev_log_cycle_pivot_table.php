<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up()
    {
        DB::statement("
    CREATE OR REPLACE VIEW cycle_complete_analytics AS
    WITH cycle_boundaries AS (
        -- Get cycle boundaries
        SELECT
            id,
            cycle_id,
            vehicle_id,
            COUNT(*) as total_logs,
            MIN(date) as cycle_start_date,
            MAX(date) as cycle_end_date,
            TIMESTAMPDIFF(MINUTE, MIN(date), MAX(date)) as duration_minutes
        FROM ev_logs
        WHERE cycle_id IS NOT NULL
        GROUP BY id, cycle_id, vehicle_id
        HAVING COUNT(*) > 0
    ),
    first_child_in_cycle AS (
        -- Find the FIRST CHILD in each cycle
        SELECT
            l.cycle_id,
            MIN(l.id) as first_child_id,
            MIN(l.parent_id) as parent_of_first_child_id
        FROM ev_logs l
        WHERE l.cycle_id IS NOT NULL
        AND l.parent_id IS NOT NULL
        AND l.parent_id != 0
        GROUP BY l.cycle_id
    ),
    last_child_in_cycle AS (
        -- Find the LAST CHILD in each cycle
        SELECT
            l.cycle_id,
            MAX(l.id) as last_child_id
        FROM ev_logs l
        WHERE l.cycle_id IS NOT NULL
        AND l.parent_id IS NOT NULL
        AND l.parent_id != 0
        GROUP BY l.cycle_id
    ),
    parent_of_first_child_data AS (
        -- Get ALL ITEMS from the PARENT of the FIRST CHILD (START VALUES)
        SELECT
            p.id,
            fc.cycle_id,
            p.odo as start_odo,
            p.voltage as start_voltage,
            p.soc as start_soc,
            p.aca as start_aca,
            p.ada as start_ada,
            p.ac as start_ac,
            p.ad as start_ad,
            p.lvc as start_lvc,
            p.hvc as start_hvc,
            p.ltc as start_ltc,
            p.htc as start_htc,
            p.tc as start_tc
        FROM first_child_in_cycle fc
        LEFT JOIN ev_log_pivot p ON fc.parent_of_first_child_id = p.id
    ),
    last_child_data AS (
        -- Get ALL ITEMS from the LAST CHILD (END VALUES)
        SELECT
            lc.cycle_id,
            p.odo as end_odo,
            p.voltage as end_voltage,
            p.soc as end_soc,
            p.aca as end_aca,
            p.ada as end_ada,
            p.ac as end_ac,
            p.ad as end_ad,
            p.lvc as end_lvc,
            p.hvc as end_hvc,
            p.ltc as end_ltc,
            p.htc as end_htc,
            p.tc as end_tc
        FROM last_child_in_cycle lc
        LEFT JOIN ev_log_pivot p ON lc.last_child_id = p.id
    ),
    next_cycle_start_data AS (
        -- Get the FIRST CHILD of the LAST CHILD (next cycle start)
        SELECT
            lc.cycle_id,
            MIN(el.id) as next_start_id
        FROM last_child_in_cycle lc
        LEFT JOIN ev_logs el ON el.parent_id = lc.last_child_id
        GROUP BY lc.cycle_id
    ),
    next_cycle_metrics AS (
        -- Get ALL ITEMS for next cycle start
        SELECT
            ncsd.cycle_id,
            p.odo as next_start_odo,
            p.voltage as next_start_voltage,
            p.soc as next_cycle_soc,
            p.aca as next_start_aca,
            p.ada as next_start_ada,
            p.ac as next_start_ac,
            p.ad as next_start_ad,
            p.lvc as next_start_lvc,
            p.hvc as next_start_hvc,
            p.ltc as next_start_ltc,
            p.htc as next_start_htc,
            p.tc as next_start_tc,
            p.date as next_start_date
        FROM next_cycle_start_data ncsd
        LEFT JOIN ev_log_pivot p ON ncsd.next_start_id = p.id
    )
    SELECT
        cb.id,
        cb.cycle_id,
        cb.vehicle_id,
        cb.total_logs,
        cb.cycle_start_date,
        cb.cycle_end_date,
        cb.duration_minutes,

        -- START VALUES (from parent of first child)
        pfc.start_odo,
        pfc.start_voltage,
        pfc.start_soc,
        pfc.start_aca,
        pfc.start_ada,
        pfc.start_ac,
        pfc.start_ad,
        pfc.start_lvc,
        pfc.start_hvc,
        pfc.start_ltc,
        pfc.start_htc,
        pfc.start_tc,

        -- END VALUES (from last child)
        lcd.end_odo,
        lcd.end_voltage,
        lcd.end_soc,
        lcd.end_aca,
        lcd.end_ada,
        lcd.end_ac,
        lcd.end_ad,
        lcd.end_lvc,
        lcd.end_hvc,
        lcd.end_ltc,
        lcd.end_htc,
        lcd.end_tc,

        -- NEXT CYCLE START VALUES (from first child of last child)
        ncm.next_start_odo,
        ncm.next_start_voltage,
        ncm.next_cycle_soc,
        ncm.next_start_aca,
        ncm.next_start_ada,
        ncm.next_start_ac,
        ncm.next_start_ad,
        ncm.next_start_lvc,
        ncm.next_start_hvc,
        ncm.next_start_ltc,
        ncm.next_start_htc,
        ncm.next_start_tc,
        ncm.next_start_date,

        -- Calculate distance
        COALESCE(lcd.end_odo - pfc.start_odo, 0) as distance_km,

        -- Calculate differences for all metrics
        COALESCE(lcd.end_odo - pfc.start_odo, 0) as odo_delta,
        COALESCE(lcd.end_voltage - pfc.start_voltage, 0) as voltage_delta,
        COALESCE(lcd.end_soc - pfc.start_soc, 0) as soc_delta,
        COALESCE(lcd.end_aca - pfc.start_aca, 0) as aca_delta,
        COALESCE(lcd.end_ada - pfc.start_ada, 0) as ada_delta,
        COALESCE(lcd.end_ac - pfc.start_ac, 0) as ac_delta,
        COALESCE(lcd.end_ad - pfc.start_ad, 0) as ad_delta,
        COALESCE(lcd.end_lvc - pfc.start_lvc, 0) as lvc_delta,
        COALESCE(lcd.end_hvc - pfc.start_hvc, 0) as hvc_delta,
        COALESCE(lcd.end_ltc - pfc.start_ltc, 0) as ltc_delta,
        COALESCE(lcd.end_htc - pfc.start_htc, 0) as htc_delta,
        COALESCE(lcd.end_tc - pfc.start_tc, 0) as tc_delta,

        -- Efficiency metrics
        CASE
            WHEN COALESCE(lcd.end_odo - pfc.start_odo, 0) > 0
            THEN ABS(COALESCE(pfc.start_soc - lcd.end_soc, 0)) / (lcd.end_odo - pfc.start_odo)
            ELSE NULL
        END as soc_consumption_per_km

    FROM cycle_boundaries cb
    LEFT JOIN parent_of_first_child_data pfc ON cb.cycle_id = pfc.cycle_id
    LEFT JOIN last_child_data lcd ON cb.cycle_id = lcd.cycle_id
    LEFT JOIN next_cycle_metrics ncm ON cb.cycle_id = ncm.cycle_id
    WHERE pfc.start_odo IS NOT NULL
    ORDER BY cb.cycle_id DESC
");
    }

    public function down()
    {
        DB::statement('DROP VIEW IF EXISTS cycle_complete_analytics');
    }
};
