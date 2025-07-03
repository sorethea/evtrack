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
            COALESCE(l.cycle_id::text, l.id::text) AS cycle_id, -- Handle NULL cycle_id
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
    cycle_data AS (
        SELECT
            cycle_id,
            MIN(date) AS cycle_date,
            MAX(date) AS end_date,
            MIN(vehicle_id) AS vehicle_id,
            (ARRAY_AGG( (odo, soc, ac, ad) ORDER BY date ASC))[1] AS first_rec,
            (ARRAY_AGG( (odo, soc, ac, ad, lvc, hvc, ltc, htc, tc) ORDER BY date DESC))[1] AS last_rec
        FROM ev_logs_base
        GROUP BY cycle_id
    )
    SELECT
        cd.cycle_id,
        cd.vehicle_id,
        cd.cycle_date,
        cd.end_date,
        (cd.first_rec).odo AS root_odo,
        (cd.first_rec).soc AS root_soc,
        (cd.first_rec).ac AS root_ac,
        (cd.first_rec).ad AS root_ad,
        (cd.last_rec).odo AS last_odo,
        (cd.last_rec).soc AS last_soc,
        (cd.last_rec).ac AS last_ac,
        (cd.last_rec).ad AS last_ad,
        (cd.last_rec).lvc AS last_lvc,
        (cd.last_rec).hvc AS last_hvc,
        (cd.last_rec).ltc AS last_ltc,
        (cd.last_rec).htc AS last_htc,
        (cd.last_rec).tc AS last_tc,
        (cd.first_rec).soc - (cd.last_rec).soc AS soc_derivation,
        (cd.last_rec).hvc - (cd.last_rec).lvc AS v_spread,
        (cd.last_rec).htc - (cd.last_rec).ltc AS t_spread,
        (cd.last_rec).soc - 100 * ((cd.last_rec).ac - (cd.last_rec).ad) / v.capacity AS soc_middle,
        (cd.last_rec).ac - (cd.first_rec).ac AS charge,
        (cd.last_rec).ad - (cd.first_rec).ad AS discharge,
        (cd.last_rec).odo - (cd.first_rec).odo AS distance,
        -- Handle division by zero
        100 * (((cd.last_rec).ad - (cd.first_rec).ad) -
               ((cd.last_rec).ac - (cd.first_rec).ac)) /
        NULLIF((cd.last_rec).odo - (cd.first_rec).odo, 0) AS a_consumption,
        v.capacity * ((cd.first_rec).soc - (cd.last_rec).soc) /
        NULLIF((cd.last_rec).odo - (cd.first_rec).odo, 0) AS consumption
    FROM cycle_data cd
    LEFT JOIN vehicles v ON cd.vehicle_id = v.id;');
    }

    public function down(): void
    {
        DB::statement('DROP VIEW IF EXISTS ev_logs_cycle_view');
    }
};
