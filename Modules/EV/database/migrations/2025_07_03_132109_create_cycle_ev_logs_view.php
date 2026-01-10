<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('CREATE VIEW ev_logs_cycle_view AS
    WITH ev_logs_base AS (
        SELECT
            l.id AS log_id,
            l.cycle_id,  -- Remove COALESCE to use actual cycle_id
            l.vehicle_id,
            l.date,
            l.log_type,
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
        FROM ev_logs l
        LEFT JOIN ev_log_items li
            ON l.id = li.log_id
            AND li.item_id BETWEEN 1 AND 29
        WHERE l.cycle_id IS NOT NULL  -- Only include logs with actual cycle_id
        GROUP BY l.id, l.cycle_id, l.vehicle_id, l.date, l.log_type
    ),
    -- Get all unique cycles
    all_cycles AS (
        SELECT DISTINCT
            cycle_id,
            vehicle_id
        FROM ev_logs_base
    ),
    -- Order cycles by vehicle and first log date
    cycle_sequence AS (
        SELECT
            cycle_id,
            vehicle_id,
            ROW_NUMBER() OVER (PARTITION BY vehicle_id ORDER BY MIN(date)) AS cycle_order
        FROM ev_logs_base
        GROUP BY cycle_id, vehicle_id
    ),
    -- Calculate incremental changes between consecutive logs
    ev_logs_with_diffs AS (
        SELECT
            *,
            LAG(ac) OVER (PARTITION BY cycle_id ORDER BY date) AS prev_ac,
            LAG(ad) OVER (PARTITION BY cycle_id ORDER BY date) AS prev_ad,
            LAG(log_type) OVER (PARTITION BY cycle_id ORDER BY date) AS prev_log_type
        FROM ev_logs_base
    ),
    -- Separate charge accumulation by log_type
    charge_breakdown AS (
        SELECT
            cycle_id,
            SUM(CASE
                WHEN log_type = \'charging\' AND prev_ac IS NOT NULL
                THEN ac - prev_ac
                ELSE 0
            END) AS charge_from_charging,
            SUM(CASE
                WHEN log_type != \'charging\' AND prev_ac IS NOT NULL
                THEN ac - prev_ac
                ELSE 0
            END) AS charge_from_regen,
            SUM(CASE
                WHEN prev_ad IS NOT NULL
                THEN ad - prev_ad
                ELSE 0
            END) AS discharge
        FROM ev_logs_with_diffs
        GROUP BY cycle_id
    ),
    -- Get first log of each cycle (regardless of type)
    cycle_first_log AS (
        SELECT
            b1.cycle_id,
            b1.vehicle_id,
            b1.date AS cycle_date,
            b1.odo AS first_odo,
            b1.voltage AS first_voltage,
            b1.soc AS first_soc,
            b1.aca AS first_aca,
            b1.ada AS first_ada,
            b1.ac AS first_ac,
            b1.ad AS first_ad,
            b1.log_type AS first_log_type
        FROM ev_logs_base b1
        WHERE b1.date = (
            SELECT MIN(date)
            FROM ev_logs_base b2
            WHERE b2.cycle_id = b1.cycle_id
        )
    ),
    -- Get last log of each cycle
    cycle_last_log AS (
        SELECT
            b2.cycle_id,
            b2.date AS end_date,
            b2.odo AS last_odo,
            b2.soc AS last_soc,
            b2.aca AS last_aca,
            b2.ada AS last_ada,
            b2.ac AS last_ac,
            b2.ad AS last_ad,
            b2.lvc AS last_lvc,
            b2.hvc AS last_hvc,
            b2.ltc AS last_ltc,
            b2.htc AS last_htc,
            b2.tc AS last_tc,
            b2.log_type AS last_log_type
        FROM ev_logs_base b2
        WHERE b2.date = (
            SELECT MAX(date)
            FROM ev_logs_base b3
            WHERE b3.cycle_id = b2.cycle_id
        )
    ),
    -- Get previous cycles last log for chaining
    previous_cycle_data AS (
        SELECT
            cs.cycle_id AS current_cycle_id,
            cs.vehicle_id,
            cs.cycle_order,
            prev_cl.cycle_id AS previous_cycle_id,
            prev_cl.last_odo AS prev_last_odo,
            prev_cl.last_soc AS prev_last_soc,
            prev_cl.last_aca AS prev_last_aca,
            prev_cl.last_ada AS prev_last_ada,
            prev_cl.last_ac AS prev_last_ac,
            prev_cl.last_ad AS prev_last_ad,
            prev_cl.end_date AS prev_end_date
        FROM cycle_sequence cs
        LEFT JOIN cycle_sequence prev_cs ON cs.vehicle_id = prev_cs.vehicle_id
            AND cs.cycle_order = prev_cs.cycle_order + 1
        LEFT JOIN cycle_last_log prev_cl ON prev_cs.cycle_id = prev_cl.cycle_id
    )
    SELECT
        cfl.cycle_id as id,
        cfl.vehicle_id,
        cfl.cycle_date,
        cll.end_date,
        -- Previous cycle info
        pcd.previous_cycle_id,
        pcd.prev_end_date,
        -- Root values: if this cycle has a previous cycle, use previous cycles last values
    -- Otherwise use this cycles first values
        COALESCE(pcd.prev_last_odo, cfl.first_odo) AS root_odo,
        cfl.first_voltage AS root_voltage,
        COALESCE(pcd.prev_last_soc, cfl.first_soc) AS root_soc,
        COALESCE(pcd.prev_last_ac, cfl.first_ac) AS root_ac,
        COALESCE(pcd.prev_last_ad, cfl.first_ad) AS root_ad,
        COALESCE(pcd.prev_last_aca, cfl.first_aca) AS root_aca,
        COALESCE(pcd.prev_last_ada, cfl.first_ada) AS root_ada,
        -- Last values
        cll.last_odo,
        cll.last_soc,
        cll.last_aca,
        cll.last_ada,
        cll.last_ac,
        cll.last_ad,
        cll.last_lvc,
        cll.last_hvc,
        cll.last_ltc,
        cll.last_htc,
        cll.last_tc,
        -- Root and last log types
        cfl.first_log_type AS root_log_type,
        cll.last_log_type,
        -- Charge breakdown
        cb.charge_from_charging,
        cb.charge_from_regen,
        cb.discharge,
        -- Calculations using chained values
        COALESCE(pcd.prev_last_soc, cfl.first_soc) - cll.last_soc AS soc_derivation,
        cll.last_hvc - cll.last_lvc AS v_spread,
        cll.last_htc - cll.last_ltc AS t_spread,
        cll.last_soc - 100 * (cll.last_ac - cll.last_ad) / v.capacity AS soc_middle,
        cll.last_ac - cll.last_ad AS middle,
        cll.last_aca - COALESCE(pcd.prev_last_aca, cfl.first_aca) AS charge_amp,
        cll.last_ada - COALESCE(pcd.prev_last_ada, cfl.first_ada) AS discharge_amp,
        -- Original charge calculation (total)
        cll.last_ac - COALESCE(pcd.prev_last_ac, cfl.first_ac) AS charge,
        -- Percentage calculations using separated values
        100 * cb.charge_from_charging / NULLIF(cb.discharge, 0) AS percentage_charge_from_charging,
        100 * cb.charge_from_regen / NULLIF(cb.discharge, 0) AS percentage_charge_from_regen,
        -- Original calculations
        CASE
            WHEN (cll.last_ad - COALESCE(pcd.prev_last_ad, cfl.first_ad)) = 0 THEN 0
            ELSE 100 * (cll.last_ac - COALESCE(pcd.prev_last_ac, cfl.first_ac)) / (cll.last_ad - COALESCE(pcd.prev_last_ad, cfl.first_ad))
        END AS percentage_charge_total,
        cb.discharge - cb.charge_from_regen AS used_energy,
        CASE
            WHEN (COALESCE(pcd.prev_last_soc, cfl.first_soc) - cll.last_soc) = 0 THEN 0
            ELSE 100 * (cll.last_odo - COALESCE(pcd.prev_last_odo, cfl.first_odo)) / (COALESCE(pcd.prev_last_soc, cfl.first_soc) - cll.last_soc)
        END AS `range`,
        cll.last_odo - COALESCE(pcd.prev_last_odo, cfl.first_odo) AS distance,
        CASE
            WHEN (COALESCE(pcd.prev_last_soc, cfl.first_soc) - cll.last_soc) = 0 THEN 0
            ELSE 100 * ((cll.last_ada - COALESCE(pcd.prev_last_ada, cfl.first_ada)) - (cll.last_aca - COALESCE(pcd.prev_last_aca, cfl.first_aca))) /
                (COALESCE(pcd.prev_last_soc, cfl.first_soc) - cll.last_soc)
        END AS capacity_amp,
        CASE
            WHEN (COALESCE(pcd.prev_last_soc, cfl.first_soc) - cll.last_soc) = 0 THEN 0
            ELSE 100 * ((cll.last_ad - COALESCE(pcd.prev_last_ad, cfl.first_ad)) - (cll.last_ac - COALESCE(pcd.prev_last_ac, cfl.first_ac))) /
                (COALESCE(pcd.prev_last_soc, cfl.first_soc) - cll.last_soc)
        END AS capacity,
        CASE
            WHEN (cll.last_odo - COALESCE(pcd.prev_last_odo, cfl.first_odo)) = 0 THEN 0
            ELSE 1000 * (cll.last_ada - COALESCE(pcd.prev_last_ada, cfl.first_ada)) / (cll.last_odo - COALESCE(pcd.prev_last_odo, cfl.first_odo))
        END AS a_consumption_amp,
        CASE
            WHEN (cll.last_odo - COALESCE(pcd.prev_last_odo, cfl.first_odo)) = 0 THEN 0
            ELSE 1000 * (cll.last_ad - COALESCE(pcd.prev_last_ad, cfl.first_ad)) / (cll.last_odo - COALESCE(pcd.prev_last_odo, cfl.first_odo))
        END AS a_consumption,
        CASE
            WHEN (cll.last_odo - COALESCE(pcd.prev_last_odo, cfl.first_odo)) = 0 THEN 0
            ELSE 10 * v.capacity * (COALESCE(pcd.prev_last_soc, cfl.first_soc) - cll.last_soc) /
                (cll.last_odo - COALESCE(pcd.prev_last_odo, cfl.first_odo))
        END AS consumption
    FROM cycle_first_log cfl
    JOIN cycle_last_log cll ON cfl.cycle_id = cll.cycle_id AND cfl.vehicle_id = cll.vehicle_id
    JOIN charge_breakdown cb ON cfl.cycle_id = cb.cycle_id
    LEFT JOIN previous_cycle_data pcd ON cfl.cycle_id = pcd.current_cycle_id AND cfl.vehicle_id = pcd.vehicle_id
    LEFT JOIN vehicles v ON cfl.vehicle_id = v.id
    ORDER BY cfl.cycle_date;');
        /*
        DB::statement('CREATE VIEW ev_logs_cycle_view AS
    WITH ev_logs_base AS (
        SELECT
            l.id AS log_id,
            COALESCE(CAST(l.cycle_id AS CHAR), CAST(l.id AS CHAR)) AS cycle_id,
            l.vehicle_id,
            l.date,
            l.log_type,
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
        FROM ev_logs l
        LEFT JOIN ev_log_items li
            ON l.id = li.log_id
            AND li.item_id BETWEEN 1 AND 29
        GROUP BY l.id, l.cycle_id, l.vehicle_id, l.date, l.log_type
    ),
    cycle_roots AS (
        SELECT
            b1.cycle_id,
            b1.vehicle_id,
            b1.date AS cycle_date,
            b1.odo AS root_odo,
            b1.voltage AS root_voltage,
            b1.soc AS root_soc,
            b1.aca AS root_aca,
            b1.ada AS root_ada,
            b1.ac AS root_ac,
            b1.ad AS root_ad
        FROM ev_logs_base b1
        WHERE b1.log_type = \'charging\'
        AND b1.date = (
            SELECT MIN(date)
            FROM ev_logs_base b2
            WHERE b2.cycle_id = b1.cycle_id
            AND b2.log_type = \'charging\'
        )
    ),
    last_in_cycle AS (
        SELECT
            b2.cycle_id,
            b2.date AS end_date,
            b2.odo AS last_odo,
            b2.soc AS last_soc,
            b2.aca AS last_aca,
            b2.ada AS last_ada,
            b2.ac AS last_ac,
            b2.ad AS last_ad,
            b2.lvc AS last_lvc,
            b2.hvc AS last_hvc,
            b2.ltc AS last_ltc,
            b2.htc AS last_htc,
            b2.tc AS last_tc
        FROM ev_logs_base b2
        INNER JOIN (
            SELECT cycle_id, MAX(date) AS max_date
            FROM ev_logs_base
            GROUP BY cycle_id
        ) l ON b2.cycle_id = l.cycle_id AND b2.date = l.max_date
    )
    SELECT
        cr.cycle_id as id,
        cr.vehicle_id,
        cr.cycle_date,
        lic.end_date,
        cr.root_odo,
        cr.root_voltage,
        cr.root_soc,
        cr.root_ac,
        cr.root_ad,
        cr.root_aca,
        cr.root_ada,
        lic.last_odo,
        lic.last_soc,
        lic.last_aca,
        lic.last_ada,
        lic.last_ac,
        lic.last_ad,
        lic.last_lvc,
        lic.last_hvc,
        lic.last_ltc,
        lic.last_htc,
        lic.last_tc,
        cr.root_soc - lic.last_soc AS soc_derivation,
        lic.last_hvc - lic.last_lvc AS v_spread,
        lic.last_htc - lic.last_ltc AS t_spread,
        lic.last_soc - 100 * (lic.last_ac - lic.last_ad) / v.capacity AS soc_middle,
        lic.last_ac - lic.last_ad middle,
        lic.last_aca - cr.root_aca AS charge_amp,
        lic.last_ada - cr.root_ada AS discharge_amp,
        lic.last_ac - cr.root_ac AS charge,
        100*(lic.last_ac - cr.root_ac)/(lic.last_ad - cr.root_ad) AS percentage_charge,
        lic.last_ad - cr.root_ad AS discharge,
        (lic.last_ad - cr.root_ad)-(lic.last_ac - cr.root_ac) AS used_energy,
        100*(lic.last_odo - cr.root_odo) / (cr.root_soc - lic.last_soc) AS `range`,
        lic.last_odo - cr.root_odo AS distance,
        100 * ((lic.last_ada - cr.root_ada) - (lic.last_aca - cr.root_aca)) /
        (cr.root_soc - lic.last_soc) AS capacity_amp,
        100 * ((lic.last_ad - cr.root_ad) - (lic.last_ac - cr.root_ac)) /
        (cr.root_soc - lic.last_soc) AS capacity,
        1000 * (lic.last_ada - cr.root_ada) /
        NULLIF(lic.last_odo - cr.root_odo, 0) AS a_consumption_amp,
        1000 * (lic.last_ad - cr.root_ad) /
        NULLIF(lic.last_odo - cr.root_odo, 0) AS a_consumption,
        10*v.capacity * (cr.root_soc - lic.last_soc) /
        NULLIF(lic.last_odo - cr.root_odo, 0) AS consumption
    FROM cycle_roots cr
    JOIN last_in_cycle lic ON cr.cycle_id = lic.cycle_id
    LEFT JOIN vehicles v ON cr.vehicle_id = v.id;');*/
    }

    public function down(): void
    {
        DB::statement('DROP VIEW IF EXISTS ev_logs_cycle_view');
    }
};
