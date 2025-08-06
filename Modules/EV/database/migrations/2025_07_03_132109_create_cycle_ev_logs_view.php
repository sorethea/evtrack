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
        lic.last_aca - cr.root_aca AS charge_amp,
        lic.last_ada - cr.root_ada AS discharge_amp,
        lic.last_ac - cr.root_ac AS charge,
        lic.last_ad - cr.root_ad AS discharge,
        100*(lic.last_odo - cr.root_odo) / (cr.root_soc - lic.last_soc) AS range,
        lic.last_odo - cr.root_odo AS distance,
        100 * ((lic.last_ada - cr.root_ada) - (lic.last_aca - cr.root_aca)) /
        (cr.root_soc - lic.last_soc) AS capacity_amp,
        100 * ((lic.last_ad - cr.root_ad) - (lic.last_ac - cr.root_ac)) /
        (cr.root_soc - lic.last_soc) AS capacity,
        100 * ((lic.last_ada - cr.root_ada) - (lic.last_aca - cr.root_aca)) /
        NULLIF(lic.last_odo - cr.root_odo, 0) AS a_consumption_amp,
        100 * ((lic.last_ad - cr.root_ad) - (lic.last_ac - cr.root_ac)) /
        NULLIF(lic.last_odo - cr.root_odo, 0) AS a_consumption,
        v.capacity * (cr.root_soc - lic.last_soc) /
        NULLIF(lic.last_odo - cr.root_odo, 0) AS consumption
    FROM cycle_roots cr
    JOIN last_in_cycle lic ON cr.cycle_id = lic.cycle_id
    LEFT JOIN vehicles v ON cr.vehicle_id = v.id;');
    }

    public function down(): void
    {
        DB::statement('DROP VIEW IF EXISTS ev_logs_cycle_view');
    }
};
