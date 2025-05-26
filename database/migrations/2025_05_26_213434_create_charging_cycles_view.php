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
        SELECT p.date AS from_date, c.date AS to_date, ROUND(c.odo - p.odo,0 )as distance
        FROM ev_logs p

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
