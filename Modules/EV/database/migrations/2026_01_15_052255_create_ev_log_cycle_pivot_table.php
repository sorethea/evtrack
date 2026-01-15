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
    )
    SELECT
        -- CREATE A UNIQUE ID FOR EACH ROW (REQUIRED BY FILAMENT)
        CONCAT('cycle_', cb.cycle_id, '_', cb.vehicle_id) as id,

        cb.cycle_id,
        cb.vehicle_id,
        cb.total_logs,
        cb.cycle_start_date,
        cb.cycle_end_date,
        cb.duration_minutes,

        -- START VALUES
        COALESCE(
            (SELECT odo FROM ev_log_pivot WHERE id = fc.parent_of_first_child_id),
            0
        ) as start_odo,

        COALESCE(
            (SELECT voltage FROM ev_log_pivot WHERE id = fc.parent_of_first_child_id),
            0
        ) as start_voltage,

        COALESCE(
            (SELECT soc FROM ev_log_pivot WHERE id = fc.parent_of_first_child_id),
            0
        ) as start_soc,

        COALESCE(
            (SELECT aca FROM ev_log_pivot WHERE id = fc.parent_of_first_child_id),
            0
        ) as start_aca,

        COALESCE(
            (SELECT ada FROM ev_log_pivot WHERE id = fc.parent_of_first_child_id),
            0
        ) as start_ada,

        COALESCE(
            (SELECT ac FROM ev_log_pivot WHERE id = fc.parent_of_first_child_id),
            0
        ) as start_ac,

        COALESCE(
            (SELECT ad FROM ev_log_pivot WHERE id = fc.parent_of_first_child_id),
            0
        ) as start_ad,

        COALESCE(
            (SELECT lvc FROM ev_log_pivot WHERE id = fc.parent_of_first_child_id),
            0
        ) as start_lvc,

        COALESCE(
            (SELECT hvc FROM ev_log_pivot WHERE id = fc.parent_of_first_child_id),
            0
        ) as start_hvc,

        COALESCE(
            (SELECT ltc FROM ev_log_pivot WHERE id = fc.parent_of_first_child_id),
            0
        ) as start_ltc,

        COALESCE(
            (SELECT htc FROM ev_log_pivot WHERE id = fc.parent_of_first_child_id),
            0
        ) as start_htc,

        COALESCE(
            (SELECT tc FROM ev_log_pivot WHERE id = fc.parent_of_first_child_id),
            0
        ) as start_tc,

        -- END VALUES
        COALESCE(
            (SELECT odo FROM ev_log_pivot WHERE id = lc.last_child_id),
            0
        ) as end_odo,

        COALESCE(
            (SELECT voltage FROM ev_log_pivot WHERE id = lc.last_child_id),
            0
        ) as end_voltage,

        COALESCE(
            (SELECT soc FROM ev_log_pivot WHERE id = lc.last_child_id),
            0
        ) as end_soc,

        COALESCE(
            (SELECT aca FROM ev_log_pivot WHERE id = lc.last_child_id),
            0
        ) as end_aca,

        COALESCE(
            (SELECT ada FROM ev_log_pivot WHERE id = lc.last_child_id),
            0
        ) as end_ada,

        COALESCE(
            (SELECT ac FROM ev_log_pivot WHERE id = lc.last_child_id),
            0
        ) as end_ac,

        COALESCE(
            (SELECT ad FROM ev_log_pivot WHERE id = lc.last_child_id),
            0
        ) as end_ad,

        COALESCE(
            (SELECT lvc FROM ev_log_pivot WHERE id = lc.last_child_id),
            0
        ) as end_lvc,

        COALESCE(
            (SELECT hvc FROM ev_log_pivot WHERE id = lc.last_child_id),
            0
        ) as end_hvc,

        COALESCE(
            (SELECT ltc FROM ev_log_pivot WHERE id = lc.last_child_id),
            0
        ) as end_ltc,

        COALESCE(
            (SELECT htc FROM ev_log_pivot WHERE id = lc.last_child_id),
            0
        ) as end_htc,

        COALESCE(
            (SELECT tc FROM ev_log_pivot WHERE id = lc.last_child_id),
            0
        ) as end_tc,

        -- NEXT CYCLE VALUES (with COALESCE to return 0 instead of NULL)
        COALESCE(
            (
                SELECT soc
                FROM ev_log_pivot
                WHERE id = (
                    SELECT MIN(id)
                    FROM ev_logs
                    WHERE parent_id = lc.last_child_id
                    LIMIT 1
                )
            ),
            0
        ) as next_cycle_soc,

        COALESCE(
            (
                SELECT odo
                FROM ev_log_pivot
                WHERE id = (
                    SELECT MIN(id)
                    FROM ev_logs
                    WHERE parent_id = lc.last_child_id
                    LIMIT 1
                )
            ),
            0
        ) as next_start_odo,

        COALESCE(
            (
                SELECT voltage
                FROM ev_log_pivot
                WHERE id = (
                    SELECT MIN(id)
                    FROM ev_logs
                    WHERE parent_id = lc.last_child_id
                    LIMIT 1
                )
            ),
            0
        ) as next_start_voltage,

        COALESCE(
            (
                SELECT aca
                FROM ev_log_pivot
                WHERE id = (
                    SELECT MIN(id)
                    FROM ev_logs
                    WHERE parent_id = lc.last_child_id
                    LIMIT 1
                )
            ),
            0
        ) as next_start_aca,

        COALESCE(
            (
                SELECT ada
                FROM ev_log_pivot
                WHERE id = (
                    SELECT MIN(id)
                    FROM ev_logs
                    WHERE parent_id = lc.last_child_id
                    LIMIT 1
                )
            ),
            0
        ) as next_start_ada,

        COALESCE(
            (
                SELECT ac
                FROM ev_log_pivot
                WHERE id = (
                    SELECT MIN(id)
                    FROM ev_logs
                    WHERE parent_id = lc.last_child_id
                    LIMIT 1
                )
            ),
            0
        ) as next_start_ac,

        COALESCE(
            (
                SELECT ad
                FROM ev_log_pivot
                WHERE id = (
                    SELECT MIN(id)
                    FROM ev_logs
                    WHERE parent_id = lc.last_child_id
                    LIMIT 1
                )
            ),
            0
        ) as next_start_ad,

        COALESCE(
            (
                SELECT lvc
                FROM ev_log_pivot
                WHERE id = (
                    SELECT MIN(id)
                    FROM ev_logs
                    WHERE parent_id = lc.last_child_id
                    LIMIT 1
                )
            ),
            0
        ) as next_start_lvc,

        COALESCE(
            (
                SELECT hvc
                FROM ev_log_pivot
                WHERE id = (
                    SELECT MIN(id)
                    FROM ev_logs
                    WHERE parent_id = lc.last_child_id
                    LIMIT 1
                )
            ),
            0
        ) as next_start_hvc,

        COALESCE(
            (
                SELECT ltc
                FROM ev_log_pivot
                WHERE id = (
                    SELECT MIN(id)
                    FROM ev_logs
                    WHERE parent_id = lc.last_child_id
                    LIMIT 1
                )
            ),
            0
        ) as next_start_ltc,

        COALESCE(
            (
                SELECT htc
                FROM ev_log_pivot
                WHERE id = (
                    SELECT MIN(id)
                    FROM ev_logs
                    WHERE parent_id = lc.last_child_id
                    LIMIT 1
                )
            ),
            0
        ) as next_start_htc,

        COALESCE(
            (
                SELECT tc
                FROM ev_log_pivot
                WHERE id = (
                    SELECT MIN(id)
                    FROM ev_logs
                    WHERE parent_id = lc.last_child_id
                    LIMIT 1
                )
            ),
            0
        ) as next_start_tc,

        COALESCE(
            (
                SELECT date
                FROM ev_log_pivot
                WHERE id = (
                    SELECT MIN(id)
                    FROM ev_logs
                    WHERE parent_id = lc.last_child_id
                    LIMIT 1
                )
            )
        ) as next_start_date,

        -- CALCULATED FIELDS
        COALESCE(
            (SELECT odo FROM ev_log_pivot WHERE id = lc.last_child_id) -
            (SELECT odo FROM ev_log_pivot WHERE id = fc.parent_of_first_child_id),
            0
        ) as distance_km,

        -- AC DELTA (your specific requirement)
        COALESCE(
            CASE WHEN
                COALESCE(
                    (
                        SELECT ac
                        FROM ev_log_pivot
                        WHERE id = (
                            SELECT MIN(id)
                            FROM ev_logs
                            WHERE parent_id = lc.last_child_id
                            LIMIT 1
                        )
                    ),
                    0
                ) > 0
                THEN
                    COALESCE((SELECT ac FROM ev_log_pivot WHERE id = lc.last_child_id), 0) -
                    COALESCE(
                        (
                            SELECT ac
                            FROM ev_log_pivot
                            WHERE id = (
                                SELECT MIN(id)
                                FROM ev_logs
                                WHERE parent_id = lc.last_child_id
                                LIMIT 1
                            )
                        ),
                        0
                    )
            END,
            0
        ) as ac_delta

    FROM cycle_boundaries cb
    LEFT JOIN first_child_in_cycle fc ON cb.cycle_id = fc.cycle_id
    LEFT JOIN last_child_in_cycle lc ON cb.cycle_id = lc.cycle_id
    ORDER BY cb.cycle_id DESC
");
    }

    public function down()
    {
        DB::statement('DROP VIEW IF EXISTS cycle_complete_analytics');
    }
};
