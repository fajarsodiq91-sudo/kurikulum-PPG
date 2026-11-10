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
            $table->foreignId('class_grade_id')->nullable()->after('generus_id')->constrained()->nullOnDelete();
            $table->string('decision')->nullable()->after('result');
            $table->foreignId('munaqosah_acceleration_request_id')->nullable()->after('decision')
                ->constrained('munaqosah_acceleration_requests')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('munaqosahs', function (Blueprint $table) {
            $table->dropConstrainedForeignId('munaqosah_acceleration_request_id');
            $table->dropColumn('decision');
            $table->dropConstrainedForeignId('class_grade_id');
        });
    }
};
