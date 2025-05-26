<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('ev_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('parent_id');
            $table->foreignId('vehicle_id')->nullable();
            $table->foreignId('cycle_id')->nullable();
            $table->string('log_type');
            $table->dateTime('date');
            $table->double('odo')->nullable();
            $table->double('soc')->nullable();
            $table->double('ac')->nullable();
            $table->double('ad')->nullable();
            $table->double('voltage')->nullable();
            $table->string('charge_type')->nullable();
            $table->double('capacity')->nullable();
            $table->double('power')->nullable();
            $table->double('power_charge')->nullable();
            $table->double('power_discharge')->nullable();
            $table->double('highest_volt_cell')->nullable();
            $table->double('lowest_volt_cell')->nullable();
            $table->double('highest_temp_cell')->nullable();
            $table->double('lowest_temp_cell')->nullable();
            $table->double('ac_power')->nullable();
            $table->double('ad_power')->nullable();
            $table->tinyText('remark')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ev_logs');
    }
};
