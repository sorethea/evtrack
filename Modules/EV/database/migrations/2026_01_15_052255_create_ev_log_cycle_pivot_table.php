<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up()
    {
        // Create ev_log_pivot with proper GROUP BY
        DB::statement("
            CREATE OR REPLACE VIEW ev_log_pivot AS
            SELECT
                el.id,
                el.parent_id,
                el.vehicle_id,
                el.cycle_id,
                el.log_type,
                el.date,
                el.created_at,
                el.updated_at,
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
            GROUP BY el.id, el.parent_id, el.vehicle_id, el.cycle_id, el.log_type, el.date, el.created_at, el.updated_at
        ");

        // Create cycle_summary using derived table
        DB::statement("
    CREATE OR REPLACE VIEW cycle_summary AS
    WITH cycle_parent AS (
        -- Get the actual parent record (where parent_id = 0 or NULL, or first in cycle)
        SELECT
            cycle_id,
            vehicle_id,
            MIN(id) as parent_log_id,
            MIN(date) as cycle_start_date
        FROM ev_logs
        WHERE cycle_id IS NOT NULL
        AND (parent_id = 0 OR parent_id IS NULL OR parent_id = '')
        GROUP BY cycle_id, vehicle_id
    ),
    cycle_children AS (
        -- Get all children in the cycle (excluding the parent)
        SELECT
            l.cycle_id,
            l.vehicle_id,
            COUNT(*) as child_count,
            MAX(l.id) as last_child_id,
            MAX(l.date) as last_child_date,
            MIN(l.date) as first_child_date
        FROM ev_logs l
        WHERE l.cycle_id IS NOT NULL
        AND l.id NOT IN (
            SELECT parent_log_id
            FROM cycle_parent cp
            WHERE cp.cycle_id = l.cycle_id
        )
        GROUP BY l.cycle_id, l.vehicle_id
    ),
    cycle_boundaries AS (
        -- Combine parent and children data
        SELECT
            COALESCE(cp.cycle_id, cc.cycle_id) as cycle_id,
            COALESCE(cp.vehicle_id, cc.vehicle_id) as vehicle_id,
            cp.parent_log_id,
            cp.cycle_start_date,
            cc.last_child_id,
            cc.last_child_date,
            cc.child_count,
            cc.first_child_date,
            COALESCE(cc.child_count, 0) + 1 as total_logs,
            TIMESTAMPDIFF(
                MINUTE,
                cp.cycle_start_date,
                COALESCE(cc.last_child_date, cp.cycle_start_date)
            ) as duration_minutes
        FROM cycle_parent cp
        LEFT JOIN cycle_children cc ON cp.cycle_id = cc.cycle_id
    ),
    parent_data AS (
        -- Get parent log metrics
        SELECT
            cb.cycle_id,
            ep.odo as start_odo,
            ep.soc as start_soc,
            ep.voltage as start_voltage,
            ep.aca as start_aca,
            ep.ada as start_ada
        FROM cycle_boundaries cb
        LEFT JOIN ev_log_pivot ep ON cb.parent_log_id = ep.id
    ),
    last_child_data AS (
        -- Get last child metrics
        SELECT
            cb.cycle_id,
            ep.odo as end_odo,
            ep.soc as last_child_soc,
            ep.voltage as last_child_voltage,
            ep.aca as last_child_aca,
            ep.ada as last_child_ada
        FROM cycle_boundaries cb
        LEFT JOIN ev_log_pivot ep ON cb.last_child_id = ep.id
    ),
    next_cycle_data AS (
        -- Get first child of last child (next cycle start)
        SELECT
            cb.cycle_id,
            MIN(el.id) as next_start_id
        FROM cycle_boundaries cb
        LEFT JOIN ev_logs el ON el.parent_id = cb.last_child_id
        GROUP BY cb.cycle_id
    ),
    next_cycle_metrics AS (
        -- Get metrics for next cycle start
        SELECT
            ncd.cycle_id,
            ep.soc as next_cycle_soc,
            ep.odo as next_start_odo,
            ep.date as next_start_date
        FROM next_cycle_data ncd
        LEFT JOIN ev_log_pivot ep ON ncd.next_start_id = ep.id
    )
    SELECT
        cb.cycle_id,
        cb.vehicle_id,
        cb.total_logs,
        cb.child_count,
        cb.cycle_start_date,
        cb.last_child_date,
        cb.duration_minutes,

        -- Parent metrics (from the actual parent record)
        pd.start_odo,
        pd.start_soc,
        pd.start_voltage,

        -- Last child metrics
        lcd.end_odo,
        lcd.last_child_soc,
        lcd.last_child_voltage,

        -- Next cycle metrics
        ncm.next_cycle_soc,
        ncm.next_start_odo,
        ncm.next_start_date,

        -- Calculate distance
        COALESCE(lcd.end_odo - pd.start_odo, 0) as distance_km,

        -- SOC changes
        COALESCE(lcd.last_child_soc - pd.start_soc, 0) as soc_change_current,
        COALESCE(ncm.next_cycle_soc - pd.start_soc, 0) as soc_change_to_next

    FROM cycle_boundaries cb
    LEFT JOIN parent_data pd ON cb.cycle_id = pd.cycle_id
    LEFT JOIN last_child_data lcd ON cb.cycle_id = lcd.cycle_id
    LEFT JOIN next_cycle_metrics ncm ON cb.cycle_id = ncm.cycle_id
    WHERE pd.start_odo IS NOT NULL
    ORDER BY cb.cycle_id DESC
");

        // Create cycle_analytics view
        DB::statement("
            CREATE OR REPLACE VIEW cycle_analytics AS
            SELECT
                cs.cycle_id,
                cs.vehicle_id,
                cs.total_logs,
                cs.start_time,
                cs.end_time,
                cs.duration_minutes,

                -- Start data
                (SELECT odo FROM ev_log_pivot WHERE id = cs.parent_log_id) as start_odo,
                (SELECT soc FROM ev_log_pivot WHERE id = cs.parent_log_id) as start_soc,
                (SELECT voltage FROM ev_log_pivot WHERE id = cs.parent_log_id) as start_voltage,

                -- End data from last child
                (SELECT odo FROM ev_log_pivot WHERE id = cs.last_child_log_id) as end_odo,
                (SELECT soc FROM ev_log_pivot WHERE id = cs.last_child_log_id) as last_child_soc,

                -- Next cycle start (first child of last child)
                (
                    SELECT soc
                    FROM ev_log_pivot
                    WHERE id = (
                        SELECT MIN(id)
                        FROM ev_logs
                        WHERE parent_id = cs.last_child_log_id
                        LIMIT 1
                    )
                ) as end_soc,

                -- Calculate distance
                COALESCE(
                    (SELECT odo FROM ev_log_pivot WHERE id = cs.last_child_log_id) -
                    (SELECT odo FROM ev_log_pivot WHERE id = cs.parent_log_id),
                    0
                ) as distance_km

            FROM cycle_summary cs
            WHERE cs.cycle_id IS NOT NULL
            ORDER BY cs.cycle_id DESC
        ");
    }

    public function down()
    {
        DB::statement('DROP VIEW IF EXISTS cycle_analytics');
        DB::statement('DROP VIEW IF EXISTS cycle_summary');
        DB::statement('DROP VIEW IF EXISTS ev_log_pivot');
    }
};
