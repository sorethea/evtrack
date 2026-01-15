<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up()
    {
        // First, disable ONLY_FULL_GROUP_BY for this session
        DB::statement("SET SESSION sql_mode=(SELECT REPLACE(@@sql_mode,'ONLY_FULL_GROUP_BY',''))");

        // 1. Create ev_log_pivot view (if not already created)
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

        // 2. Create cycle_analytics view with parent of first child
        DB::statement("
            CREATE OR REPLACE VIEW cycle_analytics AS
            WITH cycle_boundaries AS (
                -- Get cycle boundaries
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
                -- Find the FIRST CHILD in each cycle (earliest by date, excluding potential parent)
                SELECT
                    l.cycle_id,
                    MIN(l.id) as first_child_id,
                    MIN(l.parent_id) as parent_of_first_child_id
                FROM ev_logs l
                WHERE l.cycle_id IS NOT NULL
                AND l.parent_id IS NOT NULL  -- This ensures it's a child (has a parent)
                AND l.parent_id != 0         -- Exclude if parent_id is 0
                GROUP BY l.cycle_id
            ),
            last_child_in_cycle AS (
                -- Find the LAST CHILD in each cycle
                SELECT
                    l.cycle_id,
                    MAX(l.id) as last_child_id
                FROM ev_logs l
                WHERE l.cycle_id IS NOT NULL
                AND l.parent_id IS NOT NULL  -- This ensures it's a child
                AND l.parent_id != 0
                GROUP BY l.cycle_id
            ),
            parent_of_first_child_data AS (
                -- Get ODO and SOC from the PARENT of the FIRST CHILD
                SELECT
                    fc.cycle_id,
                    p.odo as start_odo,
                    p.soc as start_soc,
                    p.voltage as start_voltage,
                    p.aca as start_aca,
                    p.ada as start_ada
                FROM first_child_in_cycle fc
                LEFT JOIN ev_log_pivot p ON fc.parent_of_first_child_id = p.id
            ),
            last_child_data AS (
                -- Get data from the LAST CHILD
                SELECT
                    lc.cycle_id,
                    p.odo as end_odo,
                    p.soc as last_child_soc,
                    p.voltage as last_child_voltage,
                    p.aca as last_child_aca,
                    p.ada as last_child_ada
                FROM last_child_in_cycle lc
                LEFT JOIN ev_log_pivot p ON lc.last_child_id = p.id
            ),
            next_cycle_start_data AS (
                -- Get the FIRST CHILD of the LAST CHILD (start of next cycle)
                SELECT
                    lc.cycle_id,
                    MIN(el.id) as next_start_id
                FROM last_child_in_cycle lc
                LEFT JOIN ev_logs el ON el.parent_id = lc.last_child_id
                GROUP BY lc.cycle_id
            ),
            next_cycle_metrics AS (
                -- Get metrics for next cycle start
                SELECT
                    ncsd.cycle_id,
                    p.soc as next_cycle_soc,
                    p.odo as next_start_odo,
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

                -- START values: From PARENT of FIRST CHILD
                pfc.start_odo,
                pfc.start_soc,
                pfc.start_voltage,

                -- END values: From LAST CHILD
                lcd.end_odo,
                lcd.last_child_soc,
                lcd.last_child_voltage,

                -- NEXT CYCLE values: From FIRST CHILD of LAST CHILD
                ncm.next_cycle_soc,
                ncm.next_start_odo,
                ncm.next_start_date,

                -- Calculate distance (last child odo - parent of first child odo)
                COALESCE(lcd.end_odo - pfc.start_odo, 0) as distance_km,

                -- SOC changes
                COALESCE(lcd.last_child_soc - pfc.start_soc, 0) as soc_change_current_cycle,
                COALESCE(ncm.next_cycle_soc - pfc.start_soc, 0) as soc_change_to_next_cycle,

                -- Efficiency metrics
                CASE
                    WHEN COALESCE(lcd.end_odo - pfc.start_odo, 0) > 0
                    THEN ABS(COALESCE(pfc.start_soc - lcd.last_child_soc, 0)) / (lcd.end_odo - pfc.start_odo)
                    ELSE NULL
                END as soc_consumption_per_km

            FROM cycle_boundaries cb
            LEFT JOIN parent_of_first_child_data pfc ON cb.cycle_id = pfc.cycle_id
            LEFT JOIN last_child_data lcd ON cb.cycle_id = lcd.cycle_id
            LEFT JOIN next_cycle_metrics ncm ON cb.cycle_id = ncm.cycle_id
            WHERE pfc.start_odo IS NOT NULL
            ORDER BY cb.cycle_id DESC
        ");

        // 3. Create a debug view to see the relationships
        DB::statement("
            CREATE OR REPLACE VIEW debug_cycle_relationships AS
            SELECT
                l.cycle_id,
                l.vehicle_id,
                -- First child in cycle
                (
                    SELECT MIN(id)
                    FROM ev_logs
                    WHERE cycle_id = l.cycle_id
                    AND parent_id IS NOT NULL
                    AND parent_id != 0
                ) as first_child_id,
                -- Parent of first child
                (
                    SELECT parent_id
                    FROM ev_logs
                    WHERE id = (
                        SELECT MIN(id)
                        FROM ev_logs
                        WHERE cycle_id = l.cycle_id
                        AND parent_id IS NOT NULL
                        AND parent_id != 0
                    )
                ) as parent_of_first_child_id,
                -- Last child in cycle
                (
                    SELECT MAX(id)
                    FROM ev_logs
                    WHERE cycle_id = l.cycle_id
                    AND parent_id IS NOT NULL
                    AND parent_id != 0
                ) as last_child_id,
                -- Parent of last child (should be same as parent of first child in same cycle)
                (
                    SELECT parent_id
                    FROM ev_logs
                    WHERE id = (
                        SELECT MAX(id)
                        FROM ev_logs
                        WHERE cycle_id = l.cycle_id
                        AND parent_id IS NOT NULL
                        AND parent_id != 0
                    )
                ) as parent_of_last_child_id
            FROM ev_logs l
            WHERE l.cycle_id IS NOT NULL
            GROUP BY l.cycle_id, l.vehicle_id
            HAVING first_child_id IS NOT NULL
        ");
    }

    public function down()
    {
        DB::statement('DROP VIEW IF EXISTS debug_cycle_relationships');
        DB::statement('DROP VIEW IF EXISTS cycle_analytics');
        DB::statement('DROP VIEW IF EXISTS ev_log_pivot');
    }
};
