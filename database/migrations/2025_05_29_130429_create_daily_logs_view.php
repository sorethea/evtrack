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
        DB::statement('CREATE VIEW daily_logs_view AS
        SELECT
        l.id as parent_id,
        l.cycle_id,
        p.soc AS from_soc,
        l.soc AS to_soc,
        ROUND(v.capacity * (p.soc - l.soc)/100,1) AS energy,
        ROUND(l.ac - COALESCE(p.ac,0),1) AS a_charge,
        ROUND(l.ad - COALESCE(p.ad,0),1) AS a_discharge,
        CASE l.log_type
            WHEN  \'driving\' THEN
                ROUND(v.capacity * (p.soc - l.soc)/(l.odo - COALESCE(p.odo,0)),1)
            ELSE
                0
        END AS consumption,
        CASE l.log_type
            WHEN  \'driving\' THEN
                ROUND( 100*((l.ad - p.ad)-(l.ac-p.ac))/(l.odo - COALESCE(p.odo,0)),1)
            ELSE
                0
        END AS a_consumption,
        ROUND(l.soc - 100*(l.ac - l.ad)/v.capacity,1) AS gap_zero,
        l.highest_volt_cell - l.lowest_volt_cell AS voltage_spread,
        ROUND(l.odo - COALESCE(p.odo,0),1) AS distance
        FROM ev_logs l
        LEFT JOIN ev_logs p ON l.parent_id = p.id
        LEFT JOIN vehicles v ON l.vehicle_id = v.id
        ORDER BY l.date DESC
        ');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement('DROP VIEW IF EXISTS daily_logs_view');
    }
};
