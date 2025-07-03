<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('CREATE VIEW ev_logs_cycle_view AS
    WITH ev_logs_base AS (
        SELECT
            l.id AS log_id,
            l.cycle_id,  -- Added to group by cycle
            l.vehicle_id,
            l.parent_id,
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
        GROUP BY l.id, l.cycle_id, l.vehicle_id, l.parent_id, l.date
    ),
    ranked_logs AS (
        SELECT *,
            ROW_NUMBER() OVER (
                PARTITION BY cycle_id ORDER BY date ASC
            ) AS cycle_asc_rank,
            ROW_NUMBER() OVER (
                PARTITION BY cycle_id ORDER BY date DESC
            ) AS cycle_desc_rank
        FROM ev_logs_base
    ),
    cycle_roots AS (
        SELECT
            cycle_id, vehicle_id,
            odo, soc, ac, ad, lvc, hvc, ltc, htc, tc
        FROM ranked_logs
        WHERE cycle_asc_rank = 1
    ),
    cycle_ends AS (
        SELECT
            cycle_id, vehicle_id,
            odo, soc, ac, ad, lvc, hvc, ltc, htc, tc
        FROM ranked_logs
        WHERE cycle_desc_rank = 1
    )
    SELECT
        cr.cycle_id,
        cr.vehicle_id,
        cr.odo AS root_odo,
        cr.soc AS root_soc,
        cr.ac AS root_ac,
        cr.ad AS root_ad,
        ce.odo AS last_odo,
        ce.soc AS last_soc,
        ce.ac AS last_ac,
        ce.ad AS last_ad,
        ce.lvc AS last_lvc,
        ce.hvc AS last_hvc,
        ce.ltc AS last_ltc,
        ce.htc AS last_htc,
        ce.tc AS last_tc,
        cr.soc - ce.soc AS soc_derivation,  -- SOC change over cycle
        ce.hvc - ce.lvc AS v_spread,        -- Voltage spread at cycle end
        ce.htc - ce.ltc AS t_spread,        -- Temperature spread at cycle end
        ce.soc - 100 * (ce.ac - ce.ad) / v.capacity AS soc_middle,  -- Midpoint SOC
        ce.ac - cr.ac AS charge,            -- Total charge added
        ce.ad - cr.ad AS discharge,         -- Total discharge used
        ce.odo - cr.odo AS distance,        -- Distance traveled
        -- Adjusted average consumption (kWh/100km)
        100 * ((ce.ad - cr.ad) - (ce.ac - cr.ac)) / NULLIF(ce.odo - cr.odo, 0) AS a_consumption,
        -- Energy consumption (kWh/km)
        v.capacity * (cr.soc - ce.soc) / NULLIF(ce.odo - cr.odo, 0) AS consumption
    FROM cycle_roots cr
    JOIN cycle_ends ce ON cr.cycle_id = ce.cycle_id
    LEFT JOIN vehicles v ON cr.vehicle_id = v.id;');
    }

    public function down(): void
    {
        DB::statement('DROP VIEW IF EXISTS ev_logs_cycle_view');
    }
};
