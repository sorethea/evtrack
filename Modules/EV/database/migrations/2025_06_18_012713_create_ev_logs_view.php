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
    WITH ev_logs_base AS (
        SELECT
            l.id AS log_id,
            COALESCE(CAST(l.cycle_id AS CHAR), CAST(l.id AS CHAR)) AS cycle_id,
            l.vehicle_id,
            l.parent_id,
            l.date,
            l.log_type,
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
        GROUP BY l.id, l.cycle_id, l.vehicle_id, l.parent_id, l.date, l.log_type
    ),
    cycle_roots AS (
        SELECT
            r.cycle_id,
            r.vehicle_id,
            r.date AS cycle_date,
            r.log_id AS root_log_id,
            r.odo AS root_odo,
            r.soc AS root_soc,
            r.ac AS root_ac,
            r.ad AS root_ad
        FROM ev_logs_base r
        INNER JOIN (
            SELECT
                cycle_id,
                MIN(date) AS min_date
            FROM ev_logs_base
            WHERE log_type = \'charging\'
            GROUP BY cycle_id
        ) t ON r.cycle_id = t.cycle_id AND r.date = t.min_date
        WHERE r.log_type = \'charging\'
    ),
    last_in_cycle AS (
        SELECT
            c.cycle_id,
            c.date AS end_date,
            c.log_id AS last_log_id,
            c.odo AS last_odo,
            c.soc AS last_soc,
            c.ac AS last_ac,
            c.ad AS last_ad,
            c.lvc AS last_lvc,
            c.hvc AS last_hvc,
            c.ltc AS last_ltc,
            c.htc AS last_htc,
            c.tc AS last_tc
        FROM ev_logs_base c
        INNER JOIN (
            SELECT
                cycle_id,
                MAX(date) AS max_date
            FROM ev_logs_base
            GROUP BY cycle_id
        ) m ON c.cycle_id = m.cycle_id AND c.date = m.max_date
    )
    SELECT
        cr.cycle_id as log_id,
        cr.vehicle_id,
        cr.cycle_date,
        lc.end_date,
        cr.root_odo,
        cr.root_soc,
        cr.root_ac,
        cr.root_ad,
        lc.last_odo,
        lc.last_soc,
        lc.last_ac,
        lc.last_ad,
        lc.last_lvc,
        lc.last_hvc,
        lc.last_ltc,
        lc.last_htc,
        lc.last_tc,
        cr.root_soc - lc.last_soc AS soc_derivation,
        lc.last_hvc - lc.last_lvc AS v_spread,
        lc.last_htc - lc.last_ltc AS t_spread,
        lc.last_soc - 100 * (lc.last_ac - lc.last_ad) / v.capacity AS soc_middle,
        lc.last_ac - cr.root_ac AS charge,
        lc.last_ad - cr.root_ad AS discharge,
        lc.last_odo - cr.root_odo AS distance,
        -- Handle division by zero
        100 * ((lc.last_ad - cr.root_ad) - (lc.last_ac - cr.root_ac)) /
        NULLIF(lc.last_odo - cr.root_odo, 0) AS a_consumption,
        v.capacity * (cr.root_soc - lc.last_soc) /
        NULLIF(lc.last_odo - cr.root_odo, 0) AS consumption
    FROM cycle_roots cr
    JOIN last_in_cycle lc ON cr.cycle_id = lc.cycle_id
    LEFT JOIN vehicles v ON cr.vehicle_id = v.id;');
    }

    public function down(): void
    {
        DB::statement('DROP VIEW IF EXISTS ev_logs_view');
    }
};
