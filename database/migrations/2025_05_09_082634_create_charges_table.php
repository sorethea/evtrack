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
        Schema::create('charges', function (Blueprint $table) {
            $table->id();
            $table->date('date');
            $table->string("type")->nullable();
            $table->float("soc_from")->default(0);
            $table->float("soc_to")->default(0);
            $table->float("ac_from")->default(0);
            $table->float("ac_to")->default(0);
            $table->float("qty")->default(0);
            $table->float("price")->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('charges');
    }
};
