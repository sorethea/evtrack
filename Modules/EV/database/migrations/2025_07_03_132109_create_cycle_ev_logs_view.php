<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class CreateCycleEvLogsView extends Migration
{
    public function up()
    {
        // First, drop the view if it exists
        DB::statement('DROP VIEW IF EXISTS ev_logs_cycle_view');

        // Create the simplified view
        DB::statement("
            CREATE VIEW ev_logs_cycle_view AS
            WITH ev_logs_base AS (
                SELECT
                    l.id AS log_id,
                    l.cycle_id,
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
                WHERE l.cycle_id IS NOT NULL
                GROUP BY l.id, l.cycle_id, l.vehicle_id, l.date, l.log_type
            ),
            cycle_info AS (
                SELECT
                    cycle_id,
                    vehicle_id,
                    MIN(date) AS cycle_start_date,
                    MAX(date) AS cycle_end_date
                FROM ev_logs_base
                GROUP BY cycle_id, vehicle_id
            ),
            cycle_first_log AS (
                SELECT
                    b.cycle_id,
                    b.vehicle_id,
                    b.date AS first_date,
                    b.odo AS first_odo,
                    b.soc AS first_soc,
                    b.aca AS first_aca,
                    b.ada AS first_ada,
                    b.ac AS first_ac,
                    b.ad AS first_ad,
                    b.log_type AS first_log_type
                FROM ev_logs_base b
                WHERE b.date = (
                    SELECT MIN(date)
                    FROM ev_logs_base b2
                    WHERE b2.cycle_id = b.cycle_id
                )
            ),
            cycle_last_log AS (
                SELECT
                    b.cycle_id,
                    b.vehicle_id,
                    b.date AS last_date,
                    b.odo AS last_odo,
                    b.soc AS last_soc,
                    b.aca AS last_aca,
                    b.ada AS last_ada,
                    b.ac AS last_ac,
                    b.ad AS last_ad,
                    b.lvc AS last_lvc,
                    b.hvc AS last_hvc,
                    b.ltc AS last_ltc,
                    b.htc AS last_htc,
                    b.tc AS last_tc,
                    b.log_type AS last_log_type
                FROM ev_logs_base b
                WHERE b.date = (
                    SELECT MAX(date)
                    FROM ev_logs_base b2
                    WHERE b2.cycle_id = b.cycle_id
                )
            ),
            cycle_chains AS (
                SELECT
                    curr.cycle_id AS current_cycle_id,
                    curr.vehicle_id,
                    curr.cycle_start_date,
                    curr.cycle_end_date,
                    next_cycle.cycle_id AS next_cycle_id,
                    next_cycle.cycle_start_date AS next_cycle_start_date
                FROM cycle_info curr
                LEFT JOIN cycle_last_log curr_last ON curr.cycle_id = curr_last.cycle_id
                LEFT JOIN cycle_info next_cycle ON CAST(curr_last.log_id AS CHAR) = next_cycle.cycle_id
                    AND curr.vehicle_id = next_cycle.vehicle_id
                    AND next_cycle.cycle_start_date > curr.cycle_start_date
            ),
            cycle_root_values AS (
                SELECT
                    cc.current_cycle_id AS cycle_id,
                    cc.vehicle_id,
                    cc.cycle_start_date,
                    cc.cycle_end_date,
                    cc.next_cycle_id,
                    COALESCE(prev_last.last_odo, cfl.first_odo) AS root_odo,
                    COALESCE(prev_last.last_soc, cfl.first_soc) AS root_soc,
                    COALESCE(prev_last.last_aca, cfl.first_aca) AS root_aca,
                    COALESCE(prev_last.last_ada, cfl.first_ada) AS root_ada,
                    COALESCE(prev_last.last_ac, cfl.first_ac) AS root_ac,
                    COALESCE(prev_last.last_ad, cfl.first_ad) AS root_ad,
                    cfl.first_log_type AS root_log_type
                FROM cycle_chains cc
                LEFT JOIN cycle_first_log cfl ON cc.current_cycle_id = cfl.cycle_id
                LEFT JOIN cycle_chains prev_chain ON cc.current_cycle_id = prev_chain.next_cycle_id
                LEFT JOIN cycle_last_log prev_last ON prev_chain.current_cycle_id = prev_last.cycle_id
            )
            SELECT
                crv.cycle_id AS id,
                crv.vehicle_id,
                crv.cycle_start_date AS cycle_date,
                crv.cycle_end_date AS end_date,
                crv.next_cycle_id,
                crv.root_odo,
                crv.root_soc,
                crv.root_ac,
                crv.root_ad,
                crv.root_aca,
                crv.root_ada,
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
                crv.root_log_type,
                cll.last_log_type,
                crv.root_soc - cll.last_soc AS soc_derivation,
                cll.last_hvc - cll.last_lvc AS v_spread,
                cll.last_htc - cll.last_ltc AS t_spread,
                cll.last_soc - 100 * (cll.last_ac - cll.last_ad) / v.capacity AS soc_middle,
                cll.last_ac - cll.last_ad AS middle,
                cll.last_aca - crv.root_aca AS charge_amp,
                cll.last_ada - crv.root_ada AS discharge_amp,
                cll.last_ac - crv.root_ac AS charge,
                0 AS charge_from_charging,
                0 AS charge_from_regen,
                0 AS discharge,
                0 AS percentage_charge_from_charging,
                0 AS percentage_charge_from_regen,
                CASE
                    WHEN (cll.last_ad - crv.root_ad) = 0 THEN 0
                    ELSE 100 * (cll.last_ac - crv.root_ac) / (cll.last_ad - crv.root_ad)
                END AS percentage_charge_total,
                0 AS used_energy,
                CASE
                    WHEN (crv.root_soc - cll.last_soc) = 0 THEN 0
                    ELSE 100 * (cll.last_odo - crv.root_odo) / (crv.root_soc - cll.last_soc)
                END AS `range`,
                cll.last_odo - crv.root_odo AS distance,
                CASE
                    WHEN (crv.root_soc - cll.last_soc) = 0 THEN 0
                    ELSE 100 * ((cll.last_ada - crv.root_ada) - (cll.last_aca - crv.root_aca)) / (crv.root_soc - cll.last_soc)
                END AS capacity_amp,
                CASE
                    WHEN (crv.root_soc - cll.last_soc) = 0 THEN 0
                    ELSE 100 * ((cll.last_ad - crv.root_ad) - (cll.last_ac - crv.root_ac)) / (crv.root_soc - cll.last_soc)
                END AS capacity,
                CASE
                    WHEN (cll.last_odo - crv.root_odo) = 0 THEN 0
                    ELSE 1000 * (cll.last_ada - crv.root_ada) / (cll.last_odo - crv.root_odo)
                END AS a_consumption_amp,
                CASE
                    WHEN (cll.last_odo - crv.root_odo) = 0 THEN 0
                    ELSE 1000 * (cll.last_ad - crv.root_ad) / (cll.last_odo - crv.root_odo)
                END AS a_consumption,
                CASE
                    WHEN (cll.last_odo - crv.root_odo) = 0 THEN 0
                    ELSE 10 * v.capacity * (crv.root_soc - cll.last_soc) / (cll.last_odo - crv.root_odo)
                END AS consumption
            FROM cycle_root_values crv
            JOIN cycle_last_log cll ON crv.cycle_id = cll.cycle_id AND crv.vehicle_id = cll.vehicle_id
            LEFT JOIN vehicles v ON crv.vehicle_id = v.id
            ORDER BY crv.cycle_start_date;
        ");
    }

    public function down()
    {
        DB::statement('DROP VIEW IF EXISTS ev_logs_cycle_view');
    }
}
