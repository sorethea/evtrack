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
            SELECT
                cycle_id,
                vehicle_id,
                total_logs,
                parent_log_id,
                last_child_log_id,
                start_time,
                end_time,
                duration_minutes
            FROM (
                SELECT
                    cycle_id,
                    vehicle_id,
                    COUNT(*) as total_logs,
                    MIN(id) as parent_log_id,
                    MAX(id) as last_child_log_id,
                    MIN(date) as start_time,
                    MAX(date) as end_time,
                    TIMESTAMPDIFF(MINUTE, MIN(date), MAX(date)) as duration_minutes
                FROM ev_logs
                WHERE cycle_id IS NOT NULL
                GROUP BY cycle_id, vehicle_id
                HAVING COUNT(*) > 0
            ) as derived_table
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
