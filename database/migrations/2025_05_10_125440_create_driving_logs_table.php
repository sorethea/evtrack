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
        Schema::create('driving_logs', function (Blueprint $table) {
            $table->id();
            $table->date('date');
            $table->string('type')->nullable();
            $table->integer('odo');
            $table->float('soc_from')->default(0);
            $table->float('soc_to')->default(0);
            $table->float('ac')->default(0);
            $table->float('ad')->default(0);
            $table->float('voltage')->default(0);
            $table->tinyText('remark')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('driving_logs');
    }
};
