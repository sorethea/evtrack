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
            l.cycle_id,  -- Use actual cycle_id, no COALESCE
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
    -- Find continuous cycles (where last = next root)
    continuous_cycles AS (
        SELECT DISTINCT
            cycle_id,
            vehicle_id
        FROM ev_logs_base
    ),
    -- Get all unique cycles
    all_cycles AS (
        SELECT DISTINCT
            cycle_id,
            vehicle_id
        FROM ev_logs_base
    ),
    -- For each cycle, find previous cycle\'s last log
    cycle_relationships AS (
    SELECT
            curr.cycle_id AS current_cycle_id,
            curr.vehicle_id,
            prev.cycle_id AS previous_cycle_id,
            -- Get the date of the last log in previous cycle
    (SELECT MAX(date) FROM ev_logs_base WHERE cycle_id = prev.cycle_id) AS previous_cycle_end_date,
            -- Get the date of the first log in current cycle
    (SELECT MIN(date) FROM ev_logs_base WHERE cycle_id = curr.cycle_id) AS current_cycle_start_date
        FROM all_cycles curr
        LEFT JOIN all_cycles prev ON curr.vehicle_id = prev.vehicle_id
    AND prev.cycle_id < curr.cycle_id  -- Previous cycle has smaller ID
    AND EXISTS (
        -- Check if the last log of previous cycle exists as first log of current cycle
                SELECT 1
                FROM ev_logs_base prev_last
                WHERE prev_last.cycle_id = prev.cycle_id
    AND prev_last.date = (
    SELECT MAX(date)
                    FROM ev_logs_base
                    WHERE cycle_id = prev.cycle_id
                )
                AND EXISTS (
        SELECT 1
                    FROM ev_logs_base curr_first
                    WHERE curr_first.cycle_id = curr.cycle_id
    AND curr_first.date = (
    SELECT MIN(date)
                        FROM ev_logs_base
                        WHERE cycle_id = curr.cycle_id
                    )
                    -- Match by key metrics to ensure it\'s the same log
                    AND curr_first.odo = prev_last.odo
                    AND curr_first.soc = prev_last.soc
                    AND curr_first.ac = prev_last.ac
                    AND curr_first.ad = prev_last.ad
                )
            )
    ),
    -- Get root values for each cycle (could be from previous cycle\'s last log)
    cycle_root_values AS (
    SELECT
            cr.cycle_id,
            cr.vehicle_id,
            -- If this cycle has a previous cycle, use previous cycle\s last values
            -- Otherwise use this cycle\'s first log values
            CASE
                WHEN crf.previous_cycle_id IS NOT NULL THEN p.last_odo
                ELSE f.odo
            END AS root_odo,
            CASE
                WHEN crf.previous_cycle_id IS NOT NULL THEN p.last_voltage
                ELSE f.voltage
            END AS root_voltage,
            CASE
                WHEN crf.previous_cycle_id IS NOT NULL THEN p.last_soc
                ELSE f.soc
            END AS root_soc,
            CASE
                WHEN crf.previous_cycle_id IS NOT NULL THEN p.last_aca
                ELSE f.aca
            END AS root_aca,
            CASE
                WHEN crf.previous_cycle_id IS NOT NULL THEN p.last_ada
                ELSE f.ada
            END AS root_ada,
            CASE
                WHEN crf.previous_cycle_id IS NOT NULL THEN p.last_ac
                ELSE f.ac
            END AS root_ac,
            CASE
                WHEN crf.previous_cycle_id IS NOT NULL THEN p.last_ad
                ELSE f.ad
            END AS root_ad,
            -- Get cycle start date
            f.date AS cycle_date,
            -- Store relationship info
            crf.previous_cycle_id,
            f.log_type AS root_log_type
        FROM all_cycles cr
    -- Get first log in cycle
        LEFT JOIN ev_logs_base f ON cr.cycle_id = f.cycle_id
    AND f.date = (
    SELECT MIN(date)
                FROM ev_logs_base
                WHERE cycle_id = cr.cycle_id
            )
        -- Get cycle relationships
        LEFT JOIN cycle_relationships crf ON cr.cycle_id = crf.current_cycle_id
        -- Get previous cycle\'s last values if exists
        LEFT JOIN (
            SELECT
                cycle_id,
                odo AS last_odo,
                voltage AS last_voltage,
                soc AS last_soc,
                aca AS last_aca,
                ada AS last_ada,
                ac AS last_ac,
                ad AS last_ad
            FROM ev_logs_base
            WHERE date = (
                SELECT MAX(date)
                FROM ev_logs_base b2
                WHERE b2.cycle_id = ev_logs_base.cycle_id
            )
        ) p ON crf.previous_cycle_id = p.cycle_id
    ),
    -- Get last log in each cycle
    last_in_cycle AS (
        SELECT
            cycle_id,
            MAX(date) AS end_date,
            MAX(CASE WHEN date = (SELECT MAX(date) FROM ev_logs_base b2 WHERE b2.cycle_id = ev_logs_base.cycle_id) THEN odo END) AS last_odo,
            MAX(CASE WHEN date = (SELECT MAX(date) FROM ev_logs_base b2 WHERE b2.cycle_id = ev_logs_base.cycle_id) THEN soc END) AS last_soc,
            MAX(CASE WHEN date = (SELECT MAX(date) FROM ev_logs_base b2 WHERE b2.cycle_id = ev_logs_base.cycle_id) THEN aca END) AS last_aca,
            MAX(CASE WHEN date = (SELECT MAX(date) FROM ev_logs_base b2 WHERE b2.cycle_id = ev_logs_base.cycle_id) THEN ada END) AS last_ada,
            MAX(CASE WHEN date = (SELECT MAX(date) FROM ev_logs_base b2 WHERE b2.cycle_id = ev_logs_base.cycle_id) THEN ac END) AS last_ac,
            MAX(CASE WHEN date = (SELECT MAX(date) FROM ev_logs_base b2 WHERE b2.cycle_id = ev_logs_base.cycle_id) THEN ad END) AS last_ad,
            MAX(CASE WHEN date = (SELECT MAX(date) FROM ev_logs_base b2 WHERE b2.cycle_id = ev_logs_base.cycle_id) THEN lvc END) AS last_lvc,
            MAX(CASE WHEN date = (SELECT MAX(date) FROM ev_logs_base b2 WHERE b2.cycle_id = ev_logs_base.cycle_id) THEN hvc END) AS last_hvc,
            MAX(CASE WHEN date = (SELECT MAX(date) FROM ev_logs_base b2 WHERE b2.cycle_id = ev_logs_base.cycle_id) THEN ltc END) AS last_ltc,
            MAX(CASE WHEN date = (SELECT MAX(date) FROM ev_logs_base b2 WHERE b2.cycle_id = ev_logs_base.cycle_id) THEN htc END) AS last_htc,
            MAX(CASE WHEN date = (SELECT MAX(date) FROM ev_logs_base b2 WHERE b2.cycle_id = ev_logs_base.cycle_id) THEN tc END) AS last_tc,
            MAX(CASE WHEN date = (SELECT MAX(date) FROM ev_logs_base b2 WHERE b2.cycle_id = ev_logs_base.cycle_id) THEN log_type END) AS last_log_type
        FROM ev_logs_base
        GROUP BY cycle_id
    ),
    -- Calculate incremental changes within each cycle
    cycle_metrics AS (
        SELECT
            cycle_id,
            -- Total charge and discharge in the cycle
            MAX(ac) - MIN(ac) AS total_charge,
            MAX(ad) - MIN(ad) AS total_discharge,
            -- SOC change in the cycle
            MAX(soc) - MIN(soc) AS soc_change,
            -- Odo change in the cycle
            MAX(odo) - MIN(odo) AS odo_change
        FROM ev_logs_base
        GROUP BY cycle_id
    )
    SELECT
        crv.cycle_id as id,
        crv.vehicle_id,
        crv.cycle_date,
        lic.end_date,
        -- Show if this cycle is chained from previous
        crv.previous_cycle_id,
        -- Root values (could be from previous cycle\'s last)
        crv.root_odo,
        crv.root_voltage,
        crv.root_soc,
        crv.root_ac,
        crv.root_ad,
        crv.root_aca,
        crv.root_ada,
        -- Last values of current cycle
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
        -- Log types
        crv.root_log_type,
        lic.last_log_type,
        -- Calculations
        crv.root_soc - lic.last_soc AS soc_derivation,
        lic.last_hvc - lic.last_lvc AS v_spread,
        lic.last_htc - lic.last_ltc AS t_spread,
        lic.last_soc - 100 * (lic.last_ac - lic.last_ad) / v.capacity AS soc_middle,
        lic.last_ac - lic.last_ad AS middle,
        lic.last_aca - crv.root_aca AS charge_amp,
        lic.last_ada - crv.root_ada AS discharge_amp,
        -- Charge calculations
        lic.last_ac - crv.root_ac AS charge,
        cm.total_charge AS total_charge_in_cycle,
        cm.total_discharge AS total_discharge_in_cycle,
        -- Basic metrics
        100 * (lic.last_ac - crv.root_ac) / NULLIF(lic.last_ad - crv.root_ad, 0) AS percentage_charge_total,
        (lic.last_ad - crv.root_ad) - (lic.last_ac - crv.root_ac) AS used_energy,
        -- Range and distance (using chained odo if available)
        100 * (lic.last_odo - crv.root_odo) / NULLIF(crv.root_soc - lic.last_soc, 0) AS `range`,
        lic.last_odo - crv.root_odo AS distance,
        -- Capacity and consumption
        100 * ((lic.last_ada - crv.root_ada) - (lic.last_aca - crv.root_aca)) / NULLIF(crv.root_soc - lic.last_soc, 0) AS capacity_amp,
        100 * ((lic.last_ad - crv.root_ad) - (lic.last_ac - crv.root_ac)) / NULLIF(crv.root_soc - lic.last_soc, 0) AS capacity,
        1000 * (lic.last_ada - crv.root_ada) / NULLIF(lic.last_odo - crv.root_odo, 0) AS a_consumption_amp,
        1000 * (lic.last_ad - crv.root_ad) / NULLIF(lic.last_odo - crv.root_odo, 0) AS a_consumption,
        10 * v.capacity * (crv.root_soc - lic.last_soc) / NULLIF(lic.last_odo - crv.root_odo, 0) AS consumption
    FROM cycle_root_values crv
    JOIN last_in_cycle lic ON crv.cycle_id = lic.cycle_id
    JOIN cycle_metrics cm ON crv.cycle_id = cm.cycle_id
    LEFT JOIN vehicles v ON crv.vehicle_id = v.id
    ORDER BY crv.cycle_date;');
//        DB::statement('CREATE VIEW ev_logs_cycle_view AS
//    WITH ev_logs_base AS (
//        SELECT
//            l.id AS log_id,
//            COALESCE(CAST(l.cycle_id AS CHAR), CAST(l.id AS CHAR)) AS cycle_id,
//            l.vehicle_id,
//            l.date,
//            l.log_type,
//            MAX(CASE WHEN li.item_id = 1 THEN li.value END) AS odo,
//            MAX(CASE WHEN li.item_id = 2 THEN li.value END) AS voltage,
//            MAX(CASE WHEN li.item_id = 11 THEN li.value END) AS soc,
//            MAX(CASE WHEN li.item_id = 17 THEN li.value END) AS aca,
//            MAX(CASE WHEN li.item_id = 18 THEN li.value END) AS ada,
//            MAX(CASE WHEN li.item_id = 19 THEN li.value END) AS ac,
//            MAX(CASE WHEN li.item_id = 20 THEN li.value END) AS ad,
//            MAX(CASE WHEN li.item_id = 22 THEN li.value END) AS lvc,
//            MAX(CASE WHEN li.item_id = 24 THEN li.value END) AS hvc,
//            MAX(CASE WHEN li.item_id = 26 THEN li.value END) AS ltc,
//            MAX(CASE WHEN li.item_id = 28 THEN li.value END) AS htc,
//            MAX(CASE WHEN li.item_id = 29 THEN li.value END) AS tc
//        FROM ev_logs l
//        LEFT JOIN ev_log_items li
//            ON l.id = li.log_id
//            AND li.item_id BETWEEN 1 AND 29
//        GROUP BY l.id, l.cycle_id, l.vehicle_id, l.date, l.log_type
//    ),
//    -- Calculate incremental changes between consecutive logs
//    ev_logs_with_diffs AS (
//        SELECT
//            *,
//            LAG(ac) OVER (PARTITION BY cycle_id ORDER BY date) AS prev_ac,
//            LAG(ad) OVER (PARTITION BY cycle_id ORDER BY date) AS prev_ad,
//            LAG(soc) OVER (PARTITION BY cycle_id ORDER BY date) AS prev_soc,
//            LAG(log_type) OVER (PARTITION BY cycle_id ORDER BY date) AS prev_log_type
//        FROM ev_logs_base
//        WHERE cycle_id IS NOT NULL
//    ),
//    -- Separate charge and SOC accumulation by log_type
//    charge_breakdown AS (
//        SELECT
//            cycle_id,
//            -- Charge breakdown
//            SUM(CASE
//                WHEN log_type = \'charging\' AND prev_ac IS NOT NULL
//                THEN ac - prev_ac
//                ELSE 0
//            END) AS charge_from_charging,
//            SUM(CASE
//                WHEN log_type != \'charging\' AND prev_ac IS NOT NULL
//                THEN ac - prev_ac
//                ELSE 0
//            END) AS charge_from_regen,
//            -- SOC increase breakdown
//            SUM(CASE
//                WHEN log_type = \'charging\' AND prev_soc IS NOT NULL AND soc > prev_soc
//                THEN soc - prev_soc
//                ELSE 0
//            END) AS soc_increase_charging,
//            SUM(CASE
//                WHEN log_type != \'charging\' AND prev_soc IS NOT NULL AND soc > prev_soc
//                THEN soc - prev_soc
//                ELSE 0
//            END) AS soc_increase_regen,
//            -- Discharge and SOC decrease
//            SUM(CASE
//                WHEN prev_ad IS NOT NULL
//                THEN ad - prev_ad
//                ELSE 0
//            END) AS discharge,
//            SUM(CASE
//                WHEN prev_soc IS NOT NULL AND soc < prev_soc
//                THEN prev_soc - soc
//                ELSE 0
//            END) AS soc_decrease
//        FROM ev_logs_with_diffs
//        GROUP BY cycle_id
//    ),
//    cycle_roots AS (
//        SELECT
//            b1.cycle_id,
//            b1.vehicle_id,
//            b1.date AS cycle_date,
//            b1.odo AS root_odo,
//            b1.voltage AS root_voltage,
//            b1.soc AS root_soc,
//            b1.aca AS root_aca,
//            b1.ada AS root_ada,
//            b1.ac AS root_ac,
//            b1.ad AS root_ad
//        FROM ev_logs_base b1
//        WHERE b1.log_type = \'charging\'
//        AND b1.date = (
//            SELECT MIN(date)
//            FROM ev_logs_base b2
//            WHERE b2.cycle_id = b1.cycle_id
//            AND b2.log_type = \'charging\'
//        )
//    ),
//    last_in_cycle AS (
//        SELECT
//            b2.cycle_id,
//            b2.date AS end_date,
//            b2.odo AS last_odo,
//            b2.soc AS last_soc,
//            b2.aca AS last_aca,
//            b2.ada AS last_ada,
//            b2.ac AS last_ac,
//            b2.ad AS last_ad,
//            b2.lvc AS last_lvc,
//            b2.hvc AS last_hvc,
//            b2.ltc AS last_ltc,
//            b2.htc AS last_htc,
//            b2.tc AS last_tc
//        FROM ev_logs_base b2
//        INNER JOIN (
//            SELECT cycle_id, MAX(date) AS max_date
//            FROM ev_logs_base
//            GROUP BY cycle_id
//        ) l ON b2.cycle_id = l.cycle_id AND b2.date = l.max_date
//    )
//    SELECT
//        cr.cycle_id as id,
//        cr.vehicle_id,
//        cr.cycle_date,
//        lic.end_date,
//        cr.root_odo,
//        cr.root_voltage,
//        cr.root_soc,
//        cr.root_ac,
//        cr.root_ad,
//        cr.root_aca,
//        cr.root_ada,
//        lic.last_odo,
//        lic.last_soc,
//        lic.last_aca,
//        lic.last_ada,
//        lic.last_ac,
//        lic.last_ad,
//        lic.last_lvc,
//        lic.last_hvc,
//        lic.last_ltc,
//        lic.last_htc,
//        lic.last_tc,
//        (cr.root_soc - lic.last_soc) + cb.soc_increase_charging  AS soc_derivation,
//        lic.last_hvc - lic.last_lvc AS v_spread,
//        lic.last_htc - lic.last_ltc AS t_spread,
//        lic.last_soc - 100 * (lic.last_ac - lic.last_ad) / v.capacity AS soc_middle,
//        lic.last_ac - lic.last_ad AS middle,
//        lic.last_aca - cr.root_aca AS charge_amp,
//        lic.last_ada - cr.root_ada AS discharge_amp,
//        -- Original charge calculation (total)
//        lic.last_ac - cr.root_ac AS charge,
//        -- Separated charge values
//        cb.charge_from_charging,
//        cb.charge_from_regen,
//        -- SOC changes
//        cb.soc_increase_charging,
//        cb.soc_increase_regen,
//        cb.soc_decrease,
//        -- Percentage calculations
//        ROUND(cb.soc_increase_charging / NULLIF(cb.soc_increase_charging + cb.soc_increase_regen, 0) * 100, 2) AS soc_increase_charging_percentage,
//        ROUND(cb.soc_increase_regen / NULLIF(cb.soc_increase_charging + cb.soc_increase_regen, 0) * 100, 2) AS soc_increase_regen_percentage,
//        -- Efficiency metrics
//        ROUND(cb.charge_from_charging / NULLIF(cb.soc_increase_charging, 0), 2) AS charge_per_soc_increase,
//        ROUND(cb.charge_from_regen / NULLIF(cb.soc_increase_regen, 0), 2) AS regen_charge_per_soc_increase,
//        cb.discharge,
//        -- Percentage calculations using separated values
//        100 * cb.charge_from_charging / NULLIF(cb.discharge, 0) AS percentage_charge_from_charging,
//        100 * cb.charge_from_regen / NULLIF(cb.discharge, 0) AS percentage_charge_from_regen,
//        -- Original calculations remain but you can modify if needed
//        100*(lic.last_ac - cr.root_ac)/(lic.last_ad - cr.root_ad) AS percentage_charge_total,
//        cb.discharge - cb.charge_from_regen AS used_energy,
//        100*(lic.last_odo - cr.root_odo)/cb.soc_decrease AS `range`,
//        lic.last_odo - cr.root_odo AS distance,
//        100 * ((lic.last_ada - cr.root_ada) - (lic.last_aca - cr.root_aca)) /
//        (cr.root_soc - lic.last_soc) AS capacity_amp,
//        100 * (cb.discharge - cb.charge_from_regen) / cb.soc_decrease AS capacity,
//        1000 * (lic.last_ada - cr.root_ada) /
//        NULLIF(lic.last_odo - cr.root_odo, 0) AS a_consumption_amp,
//        1000 * (lic.last_ad - cr.root_ad) /
//        NULLIF(lic.last_odo - cr.root_odo, 0) AS a_consumption,
//        10*v.capacity * (cr.root_soc - lic.last_soc) /
//        NULLIF(lic.last_odo - cr.root_odo, 0) AS consumption
//    FROM cycle_roots cr
//    JOIN last_in_cycle lic ON cr.cycle_id = lic.cycle_id
//    JOIN charge_breakdown cb ON cr.cycle_id = cb.cycle_id
//    LEFT JOIN vehicles v ON cr.vehicle_id = v.id;');
/*        DB::statement('CREATE VIEW ev_logs_cycle_view AS
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
    -- Calculate incremental changes between consecutive logs
    ev_logs_with_diffs AS (
        SELECT
            *,
            LAG(ac) OVER (PARTITION BY cycle_id ORDER BY date) AS prev_ac,
            LAG(ad) OVER (PARTITION BY cycle_id ORDER BY date) AS prev_ad,
            LAG(log_type) OVER (PARTITION BY cycle_id ORDER BY date) AS prev_log_type
        FROM ev_logs_base
        WHERE cycle_id IS NOT NULL
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
        lic.last_ac - lic.last_ad AS middle,
        lic.last_aca - cr.root_aca AS charge_amp,
        lic.last_ada - cr.root_ada AS discharge_amp,
        -- Original charge calculation (total)
        lic.last_ac - cr.root_ac AS charge,
        -- Separated charge values
        cb.charge_from_charging,
        cb.charge_from_regen,
        cb.discharge,
        -- Percentage calculations using separated values
        100 * cb.charge_from_charging / NULLIF(cb.discharge, 0) AS percentage_charge_from_charging,
        100 * cb.charge_from_regen / NULLIF(cb.discharge, 0) AS percentage_charge_from_regen,
        -- Original calculations remain but you can modify if needed
        100*(lic.last_ac - cr.root_ac)/(lic.last_ad - cr.root_ad) AS percentage_charge_total,
        cb.discharge - cb.charge_from_regen AS used_energy,
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
    JOIN charge_breakdown cb ON cr.cycle_id = cb.cycle_id
    LEFT JOIN vehicles v ON cr.vehicle_id = v.id;');*/
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
