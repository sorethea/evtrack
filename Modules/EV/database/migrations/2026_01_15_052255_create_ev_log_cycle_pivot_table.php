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
        DB::statement("
    CREATE OR REPLACE VIEW cycle_pivot AS
    WITH cycle_parents AS (
        -- Get parent log for each cycle (assuming parent_id = 0 or NULL for cycle start)
        SELECT
            cycle_id,
            vehicle_id,
            MIN(CASE WHEN parent_id = 0 OR parent_id IS NULL THEN id END) as parent_log_id,
            MIN(date) as cycle_start_date
        FROM ev_logs
        WHERE cycle_id IS NOT NULL
        GROUP BY cycle_id, vehicle_id
    ),
    cycle_last_children AS (
        -- Get the last child in each cycle
        SELECT
            cycle_id,
            MAX(id) as last_child_id,
            MAX(date) as last_child_date
        FROM ev_logs
        WHERE cycle_id IS NOT NULL
        AND parent_id IS NOT NULL
        AND parent_id != 0
        GROUP BY cycle_id
    ),
    first_child_of_last_child AS (
        -- Get the first child of each last child
        SELECT
            lc.cycle_id,
            lc.last_child_id,
            -- Get the first child (by date) of the last child
            (
                SELECT MIN(el.id)
                FROM ev_logs el
                WHERE el.parent_id = lc.last_child_id
            ) as first_child_id,
            -- Get the last child's own data
            (
                SELECT ep.odo
                FROM ev_log_pivot ep
                WHERE ep.id = lc.last_child_id
            ) as last_child_odo
        FROM cycle_last_children lc
    )
    SELECT
        cp.cycle_id,
        cp.vehicle_id,
        cp.cycle_start_date,
        -- Parent data
        parent.odo as start_odo,
        parent.soc as start_soc,
        parent.voltage as start_voltage,
        -- Last child's first child data (for end_soc)
        child.soc as end_soc,
        child.odo as next_cycle_start_odo,
        -- Last child's own odo
        fc.last_child_odo as end_odo,
        -- Calculate distance
        fc.last_child_odo - parent.odo as distance_km,
        -- Calculate time to next cycle
        child.date as next_cycle_start_date,
        TIMESTAMPDIFF(MINUTE, cp.cycle_start_date, child.date) as cycle_duration_minutes,
        -- Other metrics you might want
        (SELECT COUNT(*) FROM ev_logs WHERE cycle_id = cp.cycle_id) as total_logs,
        (SELECT COUNT(*) FROM ev_logs WHERE parent_id = fc.last_child_id) as next_cycle_child_count
    FROM cycle_parents cp
    LEFT JOIN ev_log_pivot parent ON cp.parent_log_id = parent.id
    LEFT JOIN first_child_of_last_child fc ON cp.cycle_id = fc.cycle_id
    LEFT JOIN ev_logs child_log ON fc.first_child_id = child_log.id
    LEFT JOIN ev_log_pivot child ON child_log.id = child.id
    WHERE parent.odo IS NOT NULL
    AND fc.last_child_odo IS NOT NULL
    ORDER BY cp.cycle_id DESC
");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cycle_pivot');
    }
};
