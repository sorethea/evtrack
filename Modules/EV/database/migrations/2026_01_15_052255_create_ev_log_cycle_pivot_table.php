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
        SELECT
            cycle_id,
            vehicle_id,
            COUNT(*) as total_logs,
            MIN(date) as cycle_start_date,
            MAX(date) as cycle_end_date,
            TIMESTAMPDIFF(MINUTE, MIN(date), MAX(date)) as duration_minutes
        FROM ev_logs
        WHERE cycle_id IS NOT NULL
        GROUP BY cycle_id, vehicle_id
        HAVING COUNT(*) > 0
    ),
    first_child_in_cycle AS (
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
        SELECT
            fc.cycle_id,
            COALESCE(p.odo, 0) as start_odo,
            COALESCE(p.voltage, 0) as start_voltage,
            COALESCE(p.soc, 0) as start_soc,
            COALESCE(p.aca, 0) as start_aca,
            COALESCE(p.ada, 0) as start_ada,
            COALESCE(p.ac, 0) as start_ac,
            COALESCE(p.ad, 0) as start_ad,
            COALESCE(p.lvc, 0) as start_lvc,
            COALESCE(p.hvc, 0) as start_hvc,
            COALESCE(p.ltc, 0) as start_ltc,
            COALESCE(p.htc, 0) as start_htc,
            COALESCE(p.tc, 0) as start_tc
        FROM first_child_in_cycle fc
        LEFT JOIN ev_log_pivot p ON fc.parent_of_first_child_id = p.id
    ),
    last_child_data AS (
        SELECT
            lc.cycle_id,
            COALESCE(p.odo, 0) as end_odo,
            COALESCE(p.voltage, 0) as end_voltage,
            COALESCE(p.soc, 0) as end_soc,
            COALESCE(p.aca, 0) as end_aca,
            COALESCE(p.ada, 0) as end_ada,
            COALESCE(p.ac, 0) as end_ac,
            COALESCE(p.ad, 0) as end_ad,
            COALESCE(p.lvc, 0) as end_lvc,
            COALESCE(p.hvc, 0) as end_hvc,
            COALESCE(p.ltc, 0) as end_ltc,
            COALESCE(p.htc, 0) as end_htc,
            COALESCE(p.tc, 0) as end_tc
        FROM last_child_in_cycle lc
        LEFT JOIN ev_log_pivot p ON lc.last_child_id = p.id
    ),
    next_cycle_start_data AS (
        SELECT
            lc.cycle_id,
            MIN(el.id) as next_start_id
        FROM last_child_in_cycle lc
        LEFT JOIN ev_logs el ON el.parent_id = lc.last_child_id
        GROUP BY lc.cycle_id
    ),
    next_cycle_metrics AS (
        SELECT
            ncsd.cycle_id,
            COALESCE(p.odo, 0) as next_start_odo,
            COALESCE(p.voltage, 0) as next_start_voltage,
            COALESCE(p.soc, 0) as next_cycle_soc,
            COALESCE(p.aca, 0) as next_start_aca,
            COALESCE(p.ada, 0) as next_start_ada,
            COALESCE(p.ac, 0) as next_start_ac,
            COALESCE(p.ad, 0) as next_start_ad,
            COALESCE(p.lvc, 0) as next_start_lvc,
            COALESCE(p.hvc, 0) as next_start_hvc,
            COALESCE(p.ltc, 0) as next_start_ltc,
            COALESCE(p.htc, 0) as next_start_htc,
            COALESCE(p.tc, 0) as next_start_tc,
            p.date as next_start_date
        FROM next_cycle_start_data ncsd
        LEFT JOIN ev_log_pivot p ON ncsd.next_start_id = p.id
    )
    SELECT
        cb.cycle_id,
        cb.vehicle_id,
        cb.total_logs,
        cb.cycle_start_date,
        cb.cycle_end_date,
        cb.duration_minutes,

        -- START VALUES (from parent of first child) - Already COALESCE'd to 0
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

        -- END VALUES (from last child) - Already COALESCE'd to 0
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

        -- NEXT CYCLE START VALUES (from first child of last child) - Already COALESCE'd to 0
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

        -- Calculate distance (end_odo - start_odo)
        COALESCE(lcd.end_odo - pfc.start_odo, 0) as distance_km,

        -- CURRENT CYCLE DELTAS (end - start)
        COALESCE(lcd.end_odo - pfc.start_odo, 0) as current_cycle_odo_delta,
        COALESCE(lcd.end_voltage - pfc.start_voltage, 0) as current_cycle_voltage_delta,
        COALESCE(lcd.end_soc - pfc.start_soc, 0) as current_cycle_soc_delta,
        COALESCE(lcd.end_aca - pfc.start_aca, 0) as current_cycle_aca_delta,
        COALESCE(lcd.end_ada - pfc.start_ada, 0) as current_cycle_ada_delta,
        COALESCE(lcd.end_ac - pfc.start_ac, 0) as current_cycle_ac_delta,
        COALESCE(lcd.end_ad - pfc.start_ad, 0) as current_cycle_ad_delta,
        COALESCE(lcd.end_lvc - pfc.start_lvc, 0) as current_cycle_lvc_delta,
        COALESCE(lcd.end_hvc - pfc.start_hvc, 0) as current_cycle_hvc_delta,
        COALESCE(lcd.end_ltc - pfc.start_ltc, 0) as current_cycle_ltc_delta,
        COALESCE(lcd.end_htc - pfc.start_htc, 0) as current_cycle_htc_delta,
        COALESCE(lcd.end_tc - pfc.start_tc, 0) as current_cycle_tc_delta,

        -- CONDITIONAL NEXT CYCLE DELTAS (end - next_start, ONLY when next_start > 0)
        -- Since next_start_* are already COALESCE'd to 0, the condition >0 will work correctly
        COALESCE(
            CASE WHEN ncm.next_start_odo > 0 THEN lcd.end_odo - ncm.next_start_odo END,
            0
        ) as next_cycle_odo_delta,

        COALESCE(
            CASE WHEN ncm.next_start_voltage > 0 THEN lcd.end_voltage - ncm.next_start_voltage END,
            0
        ) as next_cycle_voltage_delta,

        COALESCE(
            CASE WHEN ncm.next_cycle_soc > 0 THEN lcd.end_soc - ncm.next_cycle_soc END,
            0
        ) as next_cycle_soc_delta,

        COALESCE(
            CASE WHEN ncm.next_start_aca > 0 THEN lcd.end_aca - ncm.next_start_aca END,
            0
        ) as next_cycle_aca_delta,

        COALESCE(
            CASE WHEN ncm.next_start_ada > 0 THEN lcd.end_ada - ncm.next_start_ada END,
            0
        ) as next_cycle_ada_delta,

        -- Your specific requirement: ac_delta = (end_ac - next_start_ac) only when next_start_ac > 0
        COALESCE(
            CASE WHEN ncm.next_start_ac > 0 THEN lcd.end_ac - ncm.next_start_ac END,
            0
        ) as ac_delta,

        COALESCE(
            CASE WHEN ncm.next_start_ad > 0 THEN lcd.end_ad - ncm.next_start_ad END,
            0
        ) as next_cycle_ad_delta,

        COALESCE(
            CASE WHEN ncm.next_start_lvc > 0 THEN lcd.end_lvc - ncm.next_start_lvc END,
            0
        ) as next_cycle_lvc_delta,

        COALESCE(
            CASE WHEN ncm.next_start_hvc > 0 THEN lcd.end_hvc - ncm.next_start_hvc END,
            0
        ) as next_cycle_hvc_delta,

        COALESCE(
            CASE WHEN ncm.next_start_ltc > 0 THEN lcd.end_ltc - ncm.next_start_ltc END,
            0
        ) as next_cycle_ltc_delta,

        COALESCE(
            CASE WHEN ncm.next_start_htc > 0 THEN lcd.end_htc - ncm.next_start_htc END,
            0
        ) as next_cycle_htc_delta,

        COALESCE(
            CASE WHEN ncm.next_start_tc > 0 THEN lcd.end_tc - ncm.next_start_tc END,
            0
        ) as next_cycle_tc_delta,

        -- Efficiency metrics
        CASE
            WHEN COALESCE(lcd.end_odo - pfc.start_odo, 0) > 0
            THEN ABS(COALESCE(pfc.start_soc - lcd.end_soc, 0)) / (lcd.end_odo - pfc.start_odo)
            ELSE 0
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
