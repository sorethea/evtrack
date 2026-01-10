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
    -- Find cycle boundaries and sequence
    cycle_boundaries AS (
        SELECT DISTINCT
            cycle_id,
            vehicle_id,
            MIN(date) AS cycle_start_date,
            MAX(date) AS cycle_end_date
        FROM ev_logs_base
        GROUP BY cycle_id, vehicle_id
    ),
    -- Order cycles by vehicle and start date
    cycle_sequence AS (
        SELECT
            cb.cycle_id,
            cb.vehicle_id,
            cb.cycle_start_date,
            cb.cycle_end_date,
            -- Find previous cycle for this vehicle
            LAG(cb.cycle_id) OVER (PARTITION BY cb.vehicle_id ORDER BY cb.cycle_start_date) AS previous_cycle_id,
            LAG(cb.cycle_end_date) OVER (PARTITION BY cb.vehicle_id ORDER BY cb.cycle_start_date) AS previous_cycle_end_date
        FROM cycle_boundaries cb
    ),
    -- Calculate incremental changes between consecutive logs
    ev_logs_with_diffs AS (
        SELECT
            *,
            LAG(ac) OVER (PARTITION BY cycle_id ORDER BY date) AS prev_ac,
            LAG(ad) OVER (PARTITION BY cycle_id ORDER BY date) AS prev_ad,
            LAG(soc) OVER (PARTITION BY cycle_id ORDER BY date) AS prev_soc,
            LAG(log_type) OVER (PARTITION BY cycle_id ORDER BY date) AS prev_log_type
        FROM ev_logs_base
        WHERE cycle_id IN (SELECT cycle_id FROM cycle_boundaries)
    ),
    -- Separate charge and SOC accumulation by log_type
    charge_breakdown AS (
        SELECT
            cycle_id,
            -- Charge breakdown
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
            -- SOC increase breakdown
            SUM(CASE
                WHEN log_type = \'charging\' AND prev_soc IS NOT NULL AND soc > prev_soc
                THEN soc - prev_soc
                ELSE 0
            END) AS soc_increase_charging,
            SUM(CASE
                WHEN log_type != \'charging\' AND prev_soc IS NOT NULL AND soc > prev_soc
                THEN soc - prev_soc
                ELSE 0
            END) AS soc_increase_regen,
            -- Discharge and SOC decrease
            SUM(CASE
                WHEN prev_ad IS NOT NULL
                THEN ad - prev_ad
                ELSE 0
            END) AS discharge,
            SUM(CASE
                WHEN prev_soc IS NOT NULL AND soc < prev_soc
                THEN prev_soc - soc
                ELSE 0
            END) AS soc_decrease
        FROM ev_logs_with_diffs
        GROUP BY cycle_id
    ),
    -- Get first charging log in each cycle (as potential root)
    cycle_charging_roots AS (
        SELECT
            b1.cycle_id,
            b1.vehicle_id,
            b1.date AS charging_root_date,
            b1.odo AS charging_root_odo,
            b1.voltage AS charging_root_voltage,
            b1.soc AS charging_root_soc,
            b1.aca AS charging_root_aca,
            b1.ada AS charging_root_ada,
            b1.ac AS charging_root_ac,
            b1.ad AS charging_root_ad
        FROM ev_logs_base b1
        WHERE b1.log_type = \'charging\'
        AND b1.date = (
            SELECT MIN(date)
            FROM ev_logs_base b2
            WHERE b2.cycle_id = b1.cycle_id
            AND b2.log_type = \'charging\'
        )
    ),
    -- Get last log in each cycle
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
    ),
    -- Get previous cycle\'s last values for chaining
    previous_cycle_last AS (
        SELECT
            cs.cycle_id,
            cs.vehicle_id,
            cs.cycle_start_date,
            lic_prev.last_odo AS prev_last_odo,
            lic_prev.last_soc AS prev_last_soc,
            lic_prev.last_aca AS prev_last_aca,
            lic_prev.last_ada AS prev_last_ada,
            lic_prev.last_ac AS prev_last_ac,
            lic_prev.last_ad AS prev_last_ad,
            lic_prev.last_lvc AS prev_last_lvc,
            lic_prev.last_hvc AS prev_last_hvc,
            lic_prev.last_ltc AS prev_last_ltc,
            lic_prev.last_htc AS prev_last_htc,
            lic_prev.last_tc AS prev_last_tc
        FROM cycle_sequence cs
        LEFT JOIN last_in_cycle lic_prev ON cs.previous_cycle_id = lic_prev.cycle_id
    ),
    -- Final cycle data with chained roots
    cycle_data AS (
        SELECT
            cb.cycle_id,
            cb.vehicle_id,
            cs.cycle_start_date AS cycle_date,
            lic.end_date,
            -- Use previous cycle\'s last values as root if available
            COALESCE(pcl.prev_last_odo, ccr.charging_root_odo) AS root_odo,
            COALESCE(pcl.prev_last_soc, ccr.charging_root_soc) AS root_soc,
            COALESCE(pcl.prev_last_aca, ccr.charging_root_aca) AS root_aca,
            COALESCE(pcl.prev_last_ada, ccr.charging_root_ada) AS root_ada,
            COALESCE(pcl.prev_last_ac, ccr.charging_root_ac) AS root_ac,
            COALESCE(pcl.prev_last_ad, ccr.charging_root_ad) AS root_ad,
            -- Last values from current cycle
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
            -- Previous cycle info
            cs.previous_cycle_id,
            cs.previous_cycle_end_date AS prev_cycle_end_date
        FROM cycle_boundaries cb
        JOIN cycle_sequence cs ON cb.cycle_id = cs.cycle_id AND cb.vehicle_id = cs.vehicle_id
        LEFT JOIN cycle_charging_roots ccr ON cb.cycle_id = ccr.cycle_id AND cb.vehicle_id = ccr.vehicle_id
        LEFT JOIN last_in_cycle lic ON cb.cycle_id = lic.cycle_id
        LEFT JOIN previous_cycle_last pcl ON cb.cycle_id = pcl.cycle_id AND cb.vehicle_id = pcl.vehicle_id
    )
    SELECT
        cd.cycle_id as id,
        cd.vehicle_id,
        cd.cycle_date,
        cd.end_date,
        -- Previous cycle info
        cd.previous_cycle_id,
        cd.prev_cycle_end_date,
        -- Root values (could be from previous cycle\'s last)
        cd.root_odo,
        cd.root_soc,
        cd.root_ac,
        cd.root_ad,
        cd.root_aca,
        cd.root_ada,
        -- Last values
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
        -- SOC derivation (using chained root)
        cd.root_soc - cd.last_soc AS soc_derivation,
        cd.last_hvc - cd.last_lvc AS v_spread,
        cd.last_htc - cd.last_ltc AS t_spread,
        cd.last_soc - 100 * (cd.last_ac - cd.last_ad) / v.capacity AS soc_middle,
        cd.last_ac - cd.last_ad AS middle,
        cd.last_aca - cd.root_aca AS charge_amp,
        cd.last_ada - cd.root_ada AS discharge_amp,
        -- Original charge calculation (total)
        cd.last_ac - cd.root_ac AS charge,
        -- Separated charge values
        cb.charge_from_charging,
        cb.charge_from_regen,
        -- SOC changes
        cb.soc_increase_charging,
        cb.soc_increase_regen,
        cb.soc_decrease,
        -- Percentage calculations
        ROUND(cb.soc_increase_charging / NULLIF(cb.soc_increase_charging + cb.soc_increase_regen, 0) * 100, 2) AS soc_increase_charging_percentage,
        ROUND(cb.soc_increase_regen / NULLIF(cb.soc_increase_charging + cb.soc_increase_regen, 0) * 100, 2) AS soc_increase_regen_percentage,
        -- Efficiency metrics
        ROUND(cb.charge_from_charging / NULLIF(cb.soc_increase_charging, 0), 2) AS charge_per_soc_increase,
        ROUND(cb.charge_from_regen / NULLIF(cb.soc_increase_regen, 0), 2) AS regen_charge_per_soc_increase,
        cb.discharge,
        -- Percentage calculations using separated values
        100 * cb.charge_from_charging / NULLIF(cb.discharge, 0) AS percentage_charge_from_charging,
        100 * cb.charge_from_regen / NULLIF(cb.discharge, 0) AS percentage_charge_from_regen,
        -- Original calculations
        CASE
            WHEN (cd.last_ad - cd.root_ad) = 0 THEN 0
            ELSE 100 * (cd.last_ac - cd.root_ac) / (cd.last_ad - cd.root_ad)
        END AS percentage_charge_total,
        cb.discharge - cb.charge_from_regen AS used_energy,
        CASE
            WHEN cb.soc_decrease = 0 THEN 0
            ELSE 100 * (cd.last_odo - cd.root_odo) / cb.soc_decrease
        END AS `range`,
        cd.last_odo - cd.root_odo AS distance,
        CASE
            WHEN (cd.root_soc - cd.last_soc) = 0 THEN 0
            ELSE 100 * ((cd.last_ada - cd.root_ada) - (cd.last_aca - cd.root_aca)) / (cd.root_soc - cd.last_soc)
        END AS capacity_amp,
        CASE
            WHEN cb.soc_decrease = 0 THEN 0
            ELSE 100 * (cb.discharge - cb.charge_from_regen) / cb.soc_decrease
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
    JOIN charge_breakdown cb ON cd.cycle_id = cb.cycle_id
    LEFT JOIN vehicles v ON cd.vehicle_id = v.id
    ORDER BY cd.cycle_date;');
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
