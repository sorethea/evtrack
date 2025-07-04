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
        Schema::create('vehicles', function (Blueprint $table) {
            $table->id();
            $table->string("name");
            $table->string("make");
            $table->string("model");
            $table->string("year");
            $table->string("vin")->nullable();
            $table->string("plate")->nullable();
            $table->text("specs")->nullable();
            $table->float("odo")->nullable();
            $table->float("soc")->nullable();
            $table->float("ac")->nullable();
            $table->float("ad")->nullable();
            $table->float("capacity")->nullable();
            $table->boolean("is_default")->default(false);
            $table->foreignId("user_id")->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vehicles');
    }
};
