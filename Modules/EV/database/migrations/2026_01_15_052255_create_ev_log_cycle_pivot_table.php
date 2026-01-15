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
        DB::statement("
    CREATE OR REPLACE VIEW debug_all_cycles AS
    SELECT
        l.cycle_id,
        l.vehicle_id,
        COUNT(*) as log_count,
        MIN(l.id) as min_log_id,
        MAX(l.id) as max_log_id,
        MIN(l.date) as min_date,
        MAX(l.date) as max_date,
        -- Get parent odo
        (SELECT odo FROM ev_log_pivot WHERE id = MIN(l.id)) as start_odo,
        -- Get last child odo
        (SELECT odo FROM ev_log_pivot WHERE id = MAX(l.id)) as end_odo,
        -- Calculate distance (will be 0 if null)
        COALESCE(
            (SELECT odo FROM ev_log_pivot WHERE id = MAX(l.id)) -
            (SELECT odo FROM ev_log_pivot WHERE id = MIN(l.id)),
            0
        ) as distance
    FROM ev_logs l
    WHERE l.cycle_id IS NOT NULL
    GROUP BY l.cycle_id, l.vehicle_id
    HAVING COUNT(*) > 0
    ORDER BY l.cycle_id DESC
");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cycle_pivot');
    }
};
