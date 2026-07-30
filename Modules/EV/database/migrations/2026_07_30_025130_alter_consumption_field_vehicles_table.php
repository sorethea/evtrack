<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void {
        Schema::table('vehicles',function (Blueprint $table){
            $table->double('consumption',10,1)->nullable()->after('capacity');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void {
        Schema::table('vehicles', function (Blueprint $table) {
            $table->dropColumn('consumption');
        });
    }
};
