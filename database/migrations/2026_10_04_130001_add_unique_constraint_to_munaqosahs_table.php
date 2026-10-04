<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('munaqosahs', function (Blueprint $table) {
            $table->unique(['generus_id', 'academic_year_id', 'semester_id', 'type']);
        });
    }

    public function down(): void
    {
        Schema::table('munaqosahs', function (Blueprint $table) {
            $table->dropUnique(['generus_id', 'academic_year_id', 'semester_id', 'type']);
        });
    }
};
