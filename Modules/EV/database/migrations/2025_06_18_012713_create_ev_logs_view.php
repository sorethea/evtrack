<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('CREATE VIEW ev_logs_view AS
    WITH ev_logs_base AS (
        SELECT
            l.id AS log_id,
            l.cycle_id,
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
            r.log_id AS root_log_id,
            r.vehicle_id,
            r.date AS root_date,
            r.odo AS root_odo,
            r.soc AS root_soc,
            r.ac AS root_ac,
            r.ad AS root_ad
        FROM ev_logs_base r
        WHERE r.log_type = \'charging\'
        AND r.date = (
            SELECT MIN(date)
            FROM ev_logs_base r2
            WHERE r2.cycle_id = r.cycle_id
            AND r2.log_type = \'charging\'
        )
    ),
    last_in_cycle AS (
        SELECT
            c.cycle_id,
            c.log_id AS last_log_id,
            c.date AS last_date,
            c.odo AS last_odo,
            c.soc AS last_soc,
            c.ac AS last_ac,
            c.ad AS last_ad
        FROM ev_logs_base c
        WHERE c.date = (
            SELECT MAX(date)
            FROM ev_logs_base c2
            WHERE c2.cycle_id = c.cycle_id
        )
    ),
    cycle_metrics AS (
        SELECT
            cr.root_log_id,
            cr.root_soc - lc.last_soc AS soc_derivation_cycle,
            lc.last_ac - cr.root_ac AS charge_cycle,
            lc.last_ad - cr.root_ad AS discharge_cycle,
            lc.last_odo - cr.root_odo AS distance_cycle,
            -- Handle division by zero
            100 * ((lc.last_ad - cr.root_ad) - (lc.last_ac - cr.root_ac)) /
            NULLIF(lc.last_odo - cr.root_odo, 0) AS a_consumption_cycle,
            v.capacity * (cr.root_soc - lc.last_soc) /
            NULLIF(lc.last_odo - cr.root_odo, 0) AS consumption_cycle
        FROM cycle_roots cr
        JOIN last_in_cycle lc ON cr.cycle_id = lc.cycle_id
        LEFT JOIN vehicles v ON cr.vehicle_id = v.id
    )
    SELECT
        b.log_id,
        b.date,
        b.odo,
        b.soc,
        b.ac,
        b.ad,
        b.lvc,
        b.hvc,
        b.ltc,
        b.htc,
        b.tc,
        -- For charging roots: show cycle metrics, else show parent-child metrics
        CASE
            WHEN b.log_id = cm.root_log_id THEN cm.soc_derivation_cycle
            ELSE p.soc - b.soc
        END AS soc_derivation,
        b.hvc - b.lvc AS v_spread,
        b.htc - b.ltc AS t_spread,
        b.soc - 100 * (b.ac - b.ad) / v.capacity AS soc_middle,
        CASE
            WHEN b.log_id = cm.root_log_id THEN cm.charge_cycle
            ELSE b.ac - p.ac
        END AS charge,
        CASE
            WHEN b.log_id = cm.root_log_id THEN cm.discharge_cycle
            ELSE b.ad - p.ad
        END AS discharge,
        CASE
            WHEN b.log_id = cm.root_log_id THEN cm.distance_cycle
            ELSE b.odo - p.odo
        END AS distance,
        CASE
            WHEN b.log_id = cm.root_log_id THEN cm.a_consumption_cycle
            ELSE 100 * (b.ad - p.ad - (b.ac - p.ac)) / NULLIF(b.odo - p.odo, 0)
        END AS a_consumption,
        CASE
            WHEN b.log_id = cm.root_log_id THEN cm.consumption_cycle
            ELSE v.capacity * (p.soc - b.soc) / NULLIF(b.odo - p.odo, 0)
        END AS consumption
    FROM ev_logs_base b
    LEFT JOIN ev_logs_base p ON b.parent_id = p.log_id
    LEFT JOIN vehicles v ON b.vehicle_id = v.id
    LEFT JOIN cycle_metrics cm ON b.log_id = cm.root_log_id;');
    }

    public function down(): void
    {
        DB::statement('DROP VIEW IF EXISTS ev_logs_view');
    }
};
