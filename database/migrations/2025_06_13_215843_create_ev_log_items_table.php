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
        Schema::create('ev_log_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('log_id')->index();
            $table->foreignId('item_id')->index();
            $table->double('value')->default(0);
            $table->decimal('latitude',8,6)->default(0);
            $table->decimal('longitude',9,6)->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ev_log_items');
    }
};
