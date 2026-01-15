<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up()
    {
        DB::statement("
            CREATE OR REPLACE VIEW cycle_analytics AS
            WITH cycle_info AS (
                SELECT
                    cycle_id,
                    vehicle_id,
                    COUNT(*) as total_logs,
                    MIN(id) as parent_log_id,
                    MAX(id) as last_child_log_id,
                    MIN(date) as cycle_start_date,
                    MAX(date) as cycle_end_date,
                    TIMESTAMPDIFF(MINUTE, MIN(date), MAX(date)) as duration_minutes
                FROM ev_logs
                WHERE cycle_id IS NOT NULL
                GROUP BY cycle_id, vehicle_id
                HAVING COUNT(*) > 0
            ),
            log_metrics AS (
                SELECT
                    el.id,
                    el.cycle_id,
                    MAX(CASE WHEN li.item_id = 1 THEN li.value END) AS odo,
                    MAX(CASE WHEN li.item_id = 11 THEN li.value END) AS soc,
                    MAX(CASE WHEN li.item_id = 2 THEN li.value END) AS voltage
                FROM ev_logs el
                LEFT JOIN ev_log_items li ON el.id = li.log_id
                GROUP BY el.id, el.cycle_id
            )
            SELECT
                ci.cycle_id,
                ci.vehicle_id,
                ci.total_logs,
                ci.cycle_start_date,
                ci.cycle_end_date,
                ci.duration_minutes,

                -- Parent log metrics
                pm.odo as start_odo,
                pm.soc as start_soc,
                pm.voltage as start_voltage,

                -- Last child metrics
                cm.odo as end_odo,
                cm.soc as last_child_soc,
                cm.voltage as end_voltage,

                -- Next cycle start (first child of last child)
                (
                    SELECT lm.soc
                    FROM ev_logs next_el
                    JOIN log_metrics lm ON next_el.id = lm.id
                    WHERE next_el.parent_id = ci.last_child_log_id
                    ORDER BY next_el.date ASC
                    LIMIT 1
                ) as next_cycle_soc,

                -- Calculate distance
                COALESCE(cm.odo - pm.odo, 0) as distance_km

            FROM cycle_info ci
            LEFT JOIN log_metrics pm ON ci.parent_log_id = pm.id
            LEFT JOIN log_metrics cm ON ci.last_child_log_id = cm.id
            ORDER BY ci.cycle_id DESC
        ");
    }

    public function down()
    {
        DB::statement('DROP VIEW IF EXISTS cycle_analytics');
    }
};
