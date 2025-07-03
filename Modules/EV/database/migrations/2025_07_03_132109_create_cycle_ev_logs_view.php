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
        GROUP BY l.id, l.cycle_id, l.vehicle_id, l.date
    ),
    first_in_cycle AS (
        SELECT
            b1.cycle_id,
            b1.vehicle_id,
            b1.date AS cycle_date,
            b1.odo AS root_odo,
            b1.soc AS root_soc,
            b1.ac AS root_ac,
            b1.ad AS root_ad
        FROM ev_logs_base b1
        INNER JOIN (
            SELECT cycle_id, MIN(date) AS min_date
            FROM ev_logs_base
            GROUP BY cycle_id
        ) f ON b1.cycle_id = f.cycle_id AND b1.date = f.min_date
    ),
    last_in_cycle AS (
        SELECT
            b2.cycle_id,
            b2.date AS end_date,
            b2.odo AS last_odo,
            b2.soc AS last_soc,
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
        fic.cycle_id,
        fic.vehicle_id,
        fic.cycle_date,
        lic.end_date,
        fic.root_odo,
        fic.root_soc,
        fic.root_ac,
        fic.root_ad,
        lic.last_odo,
        lic.last_soc,
        lic.last_ac,
        lic.last_ad,
        lic.last_lvc,
        lic.last_hvc,
        lic.last_ltc,
        lic.last_htc,
        lic.last_tc,
        fic.root_soc - lic.last_soc AS soc_derivation,
        lic.last_hvc - lic.last_lvc AS v_spread,
        lic.last_htc - lic.last_ltc AS t_spread,
        lic.last_soc - 100 * (lic.last_ac - lic.last_ad) / v.capacity AS soc_middle,
        lic.last_ac - fic.root_ac AS charge,
        lic.last_ad - fic.root_ad AS discharge,
        lic.last_odo - fic.root_odo AS distance,
        -- Handle division by zero
        100 * ((lic.last_ad - fic.root_ad) - (lic.last_ac - fic.root_ac)) /
        NULLIF(lic.last_odo - fic.root_odo, 0) AS a_consumption,
        v.capacity * (fic.root_soc - lic.last_soc) /
        NULLIF(lic.last_odo - fic.root_odo, 0) AS consumption
    FROM first_in_cycle fic
    JOIN last_in_cycle lic ON fic.cycle_id = lic.cycle_id
    LEFT JOIN vehicles v ON fic.vehicle_id = v.id;');
    }

    public function down(): void
    {
        DB::statement('DROP VIEW IF EXISTS ev_logs_cycle_view');
    }
};
