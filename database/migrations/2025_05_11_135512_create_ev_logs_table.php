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
            $table->string('log_type');
            $table->dateTime('date');
            $table->double('odo')->nullable();
            $table->double('soc')->nullable();
            $table->double('ac')->nullable();
            $table->double('ad')->nullable();
            $table->double('voltage')->nullable();
            $table->string('charge_type')->nullable();
            $table->double('capacity')->nullable();
            $table->double('distance')->nullable();
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
