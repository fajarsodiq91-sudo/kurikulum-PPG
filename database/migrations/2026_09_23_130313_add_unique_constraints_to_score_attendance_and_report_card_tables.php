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
        Schema::table('session_attendances', function (Blueprint $table) {
            $table->unique(['learning_session_id', 'generus_id']);
        });

        Schema::table('evaluation_scores', function (Blueprint $table) {
            $table->unique(['evaluation_id', 'generus_id']);
        });

        Schema::table('report_cards', function (Blueprint $table) {
            $table->unique(['generus_id', 'academic_year_id', 'semester_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('session_attendances', function (Blueprint $table) {
            $table->dropUnique(['learning_session_id', 'generus_id']);
        });

        Schema::table('evaluation_scores', function (Blueprint $table) {
            $table->dropUnique(['evaluation_id', 'generus_id']);
        });

        Schema::table('report_cards', function (Blueprint $table) {
            $table->dropUnique(['generus_id', 'academic_year_id', 'semester_id']);
        });
    }
};
