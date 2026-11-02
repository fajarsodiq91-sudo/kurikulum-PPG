<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('default_schedules', function (Blueprint $table) {
            $table->dropUnique(['level_id', 'academic_year_id', 'semester_id', 'week_number']);
            $table->dropColumn(['week_number', 'scheduled_date', 'day_of_week']);
            $table->json('days_of_week')->after('semester_id');
            $table->date('start_date')->after('days_of_week');
            $table->date('end_date')->after('start_date');
            $table->unique(['level_id', 'academic_year_id', 'semester_id']);
        });
    }

    public function down(): void
    {
        Schema::table('default_schedules', function (Blueprint $table) {
            $table->dropUnique(['level_id', 'academic_year_id', 'semester_id']);
            $table->dropColumn(['days_of_week', 'start_date', 'end_date']);
            $table->unsignedSmallInteger('week_number')->after('semester_id');
            $table->date('scheduled_date')->nullable()->after('week_number');
            $table->unsignedTinyInteger('day_of_week')->nullable()->after('scheduled_date');
            $table->unique(['level_id', 'academic_year_id', 'semester_id', 'week_number']);
        });
    }
};
