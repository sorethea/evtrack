<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class CreateCycleEvLogsView extends Migration
{
    public function up()
    {
        // First drop the view if it exists
        DB::statement('DROP VIEW IF EXISTS ev_logs_cycle_view');

        // Then create the view
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
    -- Identify cycle boundary logs (logs where id = cycle_id)
    cycle_boundary_logs AS (
        SELECT
            b.*,
            -- This log is a cycle boundary if its id equals a cycle_id
            CASE
                WHEN EXISTS (
                    SELECT 1 FROM ev_logs_base b2
                    WHERE b2.cycle_id = CAST(b.id AS CHAR)
                ) THEN 1
                ELSE 0
            END AS is_cycle_boundary
        FROM ev_logs_base b
    ),
    -- Find cycles and their relationships
    cycle_relationships AS (
        SELECT DISTINCT
            c1.cycle_id AS current_cycle_id,
            c1.vehicle_id,
            -- Find the next cycle (where this cycles boundary logs id equals next cycles cycle_id)
            c2.cycle_id AS next_cycle_id,
            b.log_id AS boundary_log_id,
            b.date AS boundary_date
        FROM ev_logs_base c1
    -- Find the boundary log for current cycle (log where log.id = current_cycle.cycle_id)
        INNER JOIN ev_logs_base b ON CAST(b.id AS CHAR) = c1.cycle_id
    -- Find the next cycle (where this boundary logs cycle_id = next cycles cycle_id)
        LEFT JOIN ev_logs_base c2 ON b.cycle_id = c2.cycle_id
    AND c2.cycle_id != c1.cycle_id  -- Not the same cycle
    AND c2.vehicle_id = c1.vehicle_id  -- Same vehicle
    AND c2.date > c1.date  -- Later in time
    ),
    -- Get all cycles with their actual logs
    cycle_logs AS (
    SELECT
            cycle_id,
            vehicle_id,
            MIN(date) AS cycle_start_date,
            MAX(date) AS cycle_end_date,
            COUNT(*) AS log_count
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
    -- Get first log of each cycle
    cycle_first_log AS (
        SELECT
            b1.cycle_id,
            b1.vehicle_id,
            b1.date AS cycle_start_date,
            b1.odo AS first_odo,
            b1.soc AS first_soc,
            b1.aca AS first_aca,
            b1.ada AS first_ada,
            b1.ac AS first_ac,
            b1.ad AS first_ad,
            b1.log_type AS first_log_type,
            b1.log_id AS first_log_id
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
            b2.date AS cycle_end_date,
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
            b2.log_type AS last_log_type,
            b2.log_id AS last_log_id
        FROM ev_logs_base b2
        WHERE b2.date = (
            SELECT MAX(date)
            FROM ev_logs_base b3
            WHERE b3.cycle_id = b2.cycle_id
        )
    ),
    -- Build cycle chain using boundary logs
    cycle_chain AS (
        SELECT
            cl.cycle_id,
            cl.vehicle_id,
            cl.cycle_start_date,
            cl.cycle_end_date,
            cl.log_count,
            -- Get previous cycle info
            cr_prev.current_cycle_id AS prev_cycle_id,
            cr_prev.boundary_log_id AS prev_boundary_log_id,
            -- Get next cycle info
            cr_next.next_cycle_id,
            cr_next.boundary_log_id AS next_boundary_log_id,
            -- Check if this cycle ends with a boundary log
            CASE
                WHEN EXISTS (
                    SELECT 1 FROM cycle_boundary_logs b
                    WHERE b.log_id = cll.last_log_id
                    AND b.is_cycle_boundary = 1
                ) THEN 1
                ELSE 0
            END AS has_boundary_end
        FROM cycle_logs cl
        LEFT JOIN cycle_last_log cll ON cl.cycle_id = cll.cycle_id
        -- Find if this cycle has a boundary log that links to a previous cycle
        LEFT JOIN cycle_relationships cr_prev ON cl.cycle_id = cr_prev.next_cycle_id
        -- Find if this cycle has a boundary log that links to a next cycle
        LEFT JOIN cycle_relationships cr_next ON cl.cycle_id = cr_next.current_cycle_id
    ),
    -- Final cycle data with proper chaining
    cycle_data AS (
        SELECT
            cc.cycle_id,
            cc.vehicle_id,
            cfl.cycle_start_date,
            cfl.first_log_id,
            cfl.first_odo,
            cfl.first_soc,
            cfl.first_aca,
            cfl.first_ada,
            cfl.first_ac,
            cfl.first_ad,
            cfl.first_log_type,
            cll.cycle_end_date,
            cll.last_log_id,
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
            cll.last_log_type,
            -- Chain info
            cc.prev_cycle_id,
            cc.next_cycle_id,
            cc.has_boundary_end,
            -- If previous cycle exists, get its last log as our root
            CASE
                WHEN cc.prev_cycle_id IS NOT NULL THEN prev_cll.last_odo
                ELSE cfl.first_odo
            END AS root_odo,
            CASE
                WHEN cc.prev_cycle_id IS NOT NULL THEN prev_cll.last_soc
                ELSE cfl.first_soc
            END AS root_soc,
            CASE
                WHEN cc.prev_cycle_id IS NOT NULL THEN prev_cll.last_aca
                ELSE cfl.first_aca
            END AS root_aca,
            CASE
                WHEN cc.prev_cycle_id IS NOT NULL THEN prev_cll.last_ada
                ELSE cfl.first_ada
            END AS root_ada,
            CASE
                WHEN cc.prev_cycle_id IS NOT NULL THEN prev_cll.last_ac
                ELSE cfl.first_ac
            END AS root_ac,
            CASE
                WHEN cc.prev_cycle_id IS NOT NULL THEN prev_cll.last_ad
                ELSE cfl.first_ad
            END AS root_ad
        FROM cycle_chain cc
        LEFT JOIN cycle_first_log cfl ON cc.cycle_id = cfl.cycle_id
        LEFT JOIN cycle_last_log cll ON cc.cycle_id = cll.cycle_id
        LEFT JOIN cycle_last_log prev_cll ON cc.prev_cycle_id = prev_cll.cycle_id
    )
    SELECT
        cd.cycle_id as id,
        cd.vehicle_id,
        cd.cycle_start_date AS cycle_date,
        cd.cycle_end_date AS end_date,
        -- Chain info
        cd.prev_cycle_id,
        cd.next_cycle_id,
        cd.has_boundary_end,
        -- Root values (from previous cycles last log if chained)
        cd.root_odo,
        cd.root_soc,
        cd.root_ac,
        cd.root_ad,
        cd.root_aca,
        cd.root_ada,
        -- Last values (current cycles last log)
        cd.last_odo,
        cd.last_soc,
        cd.last_aca,
        cd.last_ada,
        cd.last_ac,
        cd.last_ad,
        cd.last_lvc,
        cd.last_hvc,
        cd.last_ltc,
        cd.last_htc,
        cd.last_tc,
        -- Log IDs for tracking
        cd.first_log_id,
        cd.last_log_id,
        -- Root and last log types
        cd.first_log_type AS root_log_type,
        cd.last_log_type,
        -- Calculations
        cd.root_soc - cd.last_soc AS soc_derivation,
        cd.last_hvc - cd.last_lvc AS v_spread,
        cd.last_htc - cd.last_ltc AS t_spread,
        cd.last_soc - 100 * (cd.last_ac - cd.last_ad) / v.capacity AS soc_middle,
        cd.last_ac - cd.last_ad AS middle,
        cd.last_aca - cd.root_aca AS charge_amp,
        cd.last_ada - cd.root_ada AS discharge_amp,
        cd.last_ac - cd.root_ac AS charge,
        -- Charge breakdown
        cb.charge_from_charging,
        cb.charge_from_regen,
        cb.discharge,
        -- Percentage calculations
        100 * cb.charge_from_charging / NULLIF(cb.discharge, 0) AS percentage_charge_from_charging,
        100 * cb.charge_from_regen / NULLIF(cb.discharge, 0) AS percentage_charge_from_regen,
        -- Original calculations
        CASE
            WHEN (cd.last_ad - cd.root_ad) = 0 THEN 0
            ELSE 100 * (cd.last_ac - cd.root_ac) / (cd.last_ad - cd.root_ad)
        END AS percentage_charge_total,
        cb.discharge - cb.charge_from_regen AS used_energy,
        CASE
            WHEN (cd.root_soc - cd.last_soc) = 0 THEN 0
            ELSE 100 * (cd.last_odo - cd.root_odo) / (cd.root_soc - cd.last_soc)
        END AS `range`,
        cd.last_odo - cd.root_odo AS distance,
        CASE
            WHEN (cd.root_soc - cd.last_soc) = 0 THEN 0
            ELSE 100 * ((cd.last_ada - cd.root_ada) - (cd.last_aca - cd.root_aca)) /
                (cd.root_soc - cd.last_soc)
        END AS capacity_amp,
        CASE
            WHEN (cd.root_soc - cd.last_soc) = 0 THEN 0
            ELSE 100 * ((cd.last_ad - cd.root_ad) - (cd.last_ac - cd.root_ac)) /
                (cd.root_soc - cd.last_soc)
        END AS capacity,
        CASE
            WHEN (cd.last_odo - cd.root_odo) = 0 THEN 0
            ELSE 1000 * (cd.last_ada - cd.root_ada) / (cd.last_odo - cd.root_odo)
        END AS a_consumption_amp,
        CASE
            WHEN (cd.last_odo - cd.root_odo) = 0 THEN 0
            ELSE 1000 * (cd.last_ad - cd.root_ad) / (cd.last_odo - cd.root_odo)
        END AS a_consumption,
        CASE
            WHEN (cd.last_odo - cd.root_odo) = 0 THEN 0
            ELSE 10 * v.capacity * (cd.root_soc - cd.last_soc) / (cd.last_odo - cd.root_odo)
        END AS consumption
    FROM cycle_data cd
    LEFT JOIN charge_breakdown cb ON cd.cycle_id = cb.cycle_id
    LEFT JOIN vehicles v ON cd.vehicle_id = v.id
    ORDER BY cd.cycle_start_date;');
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
//        WHERE b1.date = (
//            SELECT MIN(date)
//            FROM ev_logs_base b2
//            WHERE b2.cycle_id = b1.cycle_id
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
    }

    public function down()
    {
        DB::statement('DROP VIEW IF EXISTS ev_logs_cycle_view');
    }
}
