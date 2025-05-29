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
        DB::statement('CREATE VIEW monthly_logs_view AS
        SELECT
        vehicle_id,
        DATE_FORMAT(v.from_date,\'%M\') AS `month`,
        SUM(a_charge) AS charge,
        SUM(a_discharge - a_regen) AS discharge,
        SUM(a_regen) AS regen,
        SUM(distance) AS distance
        FROM charging_cycles_view v
        WHERE YEAR(v.from_date) = YEAR(NOW())
        GROUP BY `month`, MONTH(v.from_date)
        ORDER BY MONTH(v.from_date) DESC
        ');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement('DROP VIEW IF EXISTS monthly_logs_view');
    }
};
