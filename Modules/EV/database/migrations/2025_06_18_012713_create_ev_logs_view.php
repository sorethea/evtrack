<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::statement('CREATE VIEW ev_logs_view AS
    WITH ev_logs_base AS (
        SELECT
            l.id,
            l.id as log_id,
            l.vehicle_id,
            l.parent_id,
            l.cycle_id,
            l.date,
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
        GROUP BY l.id, l.cycle_id, l.parent_id, l.vehicle_id, l.date
    )
    SELECT
        c.id,
        c.log_id,
        c.cycle_id,
        c.date,
        c.odo,
        c.voltage,
        c.soc,
        c.aca,
        c.ada,
        c.ac,
        c.ad,
        c.lvc,
        c.hvc,
        c.ltc,
        c.htc,
        c.tc,
        c.voltage/200 AS av_voltage,
        -- Handle NULL parent gracefully
        IFNULL(p.soc - c.soc, 0) AS soc_derivation,
        c.hvc - c.lvc AS v_spread,
        c.htc - c.ltc AS t_spread,
        c.soc - 100*(c.ac - c.ad)/IFNULL(v.capacity, 0) AS soc_middle,
        c.ac - c.ad AS middle,
        -- These will be NULL when no parent exists
        c.aca - IFNULL(p.aca, 0) AS charge_amp,
        c.ada - IFNULL(p.ada, 0) AS discharge_amp,
        c.ac - IFNULL(p.ac, 0) AS charge,
        -- Handle division by zero
        CASE
            WHEN (c.ad - IFNULL(p.ad, 0)) = 0 THEN 0
            ELSE 100 * (c.ac - IFNULL(p.ac, 0)) / (c.ad - IFNULL(p.ad, 0))
        END AS percentage_charge,
        c.ad - IFNULL(p.ad, 0) AS discharge,
        (c.ad - IFNULL(p.ad, 0)) - (c.ac - IFNULL(p.ac, 0)) AS used_energy,
        -- Handle NULL soc difference
        CASE
            WHEN (IFNULL(p.soc, c.soc) - c.soc) = 0 THEN 0
            ELSE 100 * ((c.ada - IFNULL(p.ada, 0)) - (c.aca - IFNULL(p.aca, 0))) / (IFNULL(p.soc, c.soc) - c.soc)
        END AS capacity_amp,
        CASE
            WHEN (IFNULL(p.soc, c.soc) - c.soc) = 0 THEN 0
            ELSE 100 * ((c.ad - IFNULL(p.ad, 0)) - (c.ac - IFNULL(p.ac, 0))) / (IFNULL(p.soc, c.soc) - c.soc)
        END AS capacity,
        c.odo - IFNULL(p.odo, c.odo) AS distance,
        CASE
            WHEN (IFNULL(p.soc, c.soc) - c.soc) = 0 THEN 0
            ELSE 100 * (c.odo - IFNULL(p.odo, c.odo)) / (IFNULL(p.soc, c.soc) - c.soc)
        END AS `range`,
        CASE
            WHEN (c.odo - IFNULL(p.odo, c.odo)) = 0 THEN 0
            ELSE 1000 * (c.ada - IFNULL(p.ada, 0)) / (c.odo - IFNULL(p.odo, c.odo))
        END AS a_consumption_amp,
        CASE
            WHEN (c.odo - IFNULL(p.odo, c.odo)) = 0 THEN 0
            ELSE 1000 * (c.ad - IFNULL(p.ad, 0)) / (c.odo - IFNULL(p.odo, c.odo))
        END AS a_consumption,
        CASE
            WHEN (c.odo - IFNULL(p.odo, c.odo)) = 0 THEN 0
            ELSE 10 * IFNULL(v.capacity, 0) * (IFNULL(p.soc, c.soc) - c.soc) / (c.odo - IFNULL(p.odo, c.odo))
        END AS consumption
    FROM ev_logs_base c
    LEFT JOIN ev_logs_base p ON c.parent_id = p.log_id
    LEFT JOIN vehicles v ON c.vehicle_id = v.id
    ORDER BY c.date;');
/*        DB::statement('CREATE VIEW ev_logs_view AS
        WITH ev_logs_base AS(
        SELECT
          l.id,
          l.id as log_id,
          l.vehicle_id,
          l.parent_id,
          l.cycle_id,
          l.date,
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
        GROUP BY l.id,l.cycle_id,l.parent_id,l.vehicle_id, l.date)
        SELECT
          c.id,
          c.log_id,
          c.cycle_id,
          c.date,
          c.odo,
          c.voltage,
          c.soc,
          c.aca,
          c.ada,
          c.ac,
          c.ad,
          c.lvc,
          c.hvc,
          c.ltc,
          c.htc,
          c.tc,
          c.voltage/200 AS av_voltage,
          p.soc - c.soc AS soc_derivation,
          c.hvc - c.lvc AS v_spread,
          c.htc - c.ltc AS t_spread,
          c.soc -100*(c.ac-c.ad)/v.capacity AS soc_middle,
          c.ac-c.ad middle,
          c.aca - p.aca AS charge_amp,
          c.ada - p.ada AS discharge_amp,
          c.ac - p.ac AS charge,
          100 * (c.ac - p.ac)/ (c.ad - p.ad) AS percentage_charge,
          c.ad - p.ad AS discharge,
          (c.ad - p.ad)-(c.ac - p.ac) AS used_energy,
          100*((c.ada - p.ada)-(c.aca - p.aca))/(p.soc - c.soc) AS capacity_amp,
          100*((c.ad - p.ad)-(c.ac - p.ac))/(p.soc - c.soc) AS capacity,
          c.odo - p.odo AS distance,
          100*(c.odo - p.odo) / (p.soc - c.soc) AS `range`,
          1000*( c.ada - p.ada)/(c.odo - p.odo) AS a_consumption_amp,
          1000*( c.ad - p.ad)/(c.odo - p.odo) AS a_consumption,
          10*v.capacity*(p.soc - c.soc)/(c.odo - p.odo) AS consumption
          FROM ev_logs_base c
          LEFT JOIN ev_logs_base p ON c.parent_id = p.log_id
          LEFT JOIN vehicles v ON c.vehicle_id =v.id
          ORDER BY c.date;
        ');*/
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement('DROP VIEW IF EXISTS ev_logs_view');
    }
};
