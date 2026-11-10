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
        Schema::create('munaqosah_schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('class_grade_id')->constrained()->cascadeOnUpdate()->cascadeOnDelete();
            $table->foreignId('academic_year_id')->constrained()->cascadeOnUpdate()->cascadeOnDelete();
            $table->foreignId('semester_id')->constrained()->cascadeOnUpdate()->cascadeOnDelete();
            $table->string('type')->default('semester-final');
            $table->text('materials')->nullable();
            $table->date('scheduled_start')->nullable();
            $table->date('scheduled_end')->nullable();
            $table->boolean('is_open')->default(false);
            $table->foreignId('opened_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['class_grade_id', 'academic_year_id', 'semester_id', 'type'], 'munaqosah_schedules_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('munaqosah_schedules');
    }
};
