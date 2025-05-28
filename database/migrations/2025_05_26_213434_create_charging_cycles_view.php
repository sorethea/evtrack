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
        DB::statement('CREATE VIEW charging_cycles_view AS
        SELECT
        p.id AS id,
        p.date AS from_date,
        c.date AS to_date,
        p.soc AS from_soc,
        c.soc AS to_soc,
        ROUND(p.soc - cp.soc,1)AS charge,
        ROUND(p.soc - c.soc,1)AS discharge,
        ROUND(p.ac - cp.ac,0)AS a_charge,
        ROUND(c.ac - p.ac,0)AS a_regen,
        ROUND(c.ad - p.ad,0)AS a_discharge,
        ROUND(100*(ROUND(c.ad - p.ad,0)-ROUND(c.ac - p.ac,0))/ROUND(c.odo - p.odo,0 ),0)AS consumption,
        ROUND(p.soc - 100*(p.ac - p.ad)/v.capacity,1) AS gap_zero,
        ROUND(c.odo - p.odo,0 )as distance
        FROM ev_logs p
        LEFT JOIN ev_logs cp ON p.parent_id = cp.id
        LEFT JOIN ev_logs v ON p.vehicle_id = v.id
        LEFT JOIN (
          SELECT *
          FROM ev_logs
          WHERE (cycle_id, soc) IN (
            SELECT cycle_id, MIN(soc)
            FROM ev_logs
            GROUP BY cycle_id
          )
        ) c ON p.id = c.cycle_id
        WHERE p.log_type = "charging";
        ');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement('DROP VIEW IF EXISTS charging_cycles_view');
    }
};
