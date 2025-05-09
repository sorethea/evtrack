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
        Schema::create('trips', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vehicle_id')->nullable();
            $table->date('date_from');
            $table->date('date_to')->nullable();
            $table->float('soc_from')->default(0);
            $table->float('soc_to')->default(0);
            $table->float('odo_from')->default(0);
            $table->float('odo_to')->default(0);
            $table->float('ac_from')->comment("Accumulative charge from")->default(0);
            $table->float('ac_to')->comment("Accumulative charge to")->default(0);
            $table->float('ad_from')->comment("Accumulative discharge")->default(0);
            $table->float('ad_to')->comment("Accumulative discharge")->default(0);
            $table->tinyText('comment')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('trips');
    }
};
