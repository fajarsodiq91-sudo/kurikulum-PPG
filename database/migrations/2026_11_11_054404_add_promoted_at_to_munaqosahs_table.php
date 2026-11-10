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
        Schema::table('munaqosahs', function (Blueprint $table) {
            $table->timestamp('promoted_at')->nullable()->after('munaqosah_acceleration_request_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('munaqosahs', function (Blueprint $table) {
            $table->dropColumn('promoted_at');
        });
    }
};
