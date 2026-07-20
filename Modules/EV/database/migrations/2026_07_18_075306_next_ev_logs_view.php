<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        DB::statement("
            CREATE OR REPLACE VIEW next_ev_logs_view AS
            SELECT
                a.id,
                a.date AS date_from,
                b.date AS date_to,
                b.ad - a.ad AS total_discharge,
                b.ac - a.ac AS total_charge,
                b.id AS next_id,
                (SELECT SUM(ac) FROM ev_logs WHERE cycle_id = a.id type='charge') AS charge,
                (SELECT SUM(ac) FROM ev_logs WHERE cycle_id = a.id type<>'charge') AS regen
            FROM ev_logs a
            LEFT JOIN ev_logs b
                ON b.id = (
                    SELECT MIN(c.id)
                    FROM ev_logs c
                    WHERE c.id > a.id AND c.soc = 100
                )
            WHERE a.soc = 100;
        ");
    }

    public function down(): void
    {
        DB::statement('DROP VIEW IF EXISTS next_ev_logs_view');
    }
};
