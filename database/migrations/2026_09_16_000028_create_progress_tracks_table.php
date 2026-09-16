<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('progress_tracks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('generus_id')->constrained('generus')->cascadeOnUpdate()->restrictOnDelete();
            $table->foreignId('academic_year_id')->constrained('academic_years')->cascadeOnUpdate()->restrictOnDelete();
            $table->foreignId('semester_id')->constrained('semesters')->cascadeOnUpdate()->restrictOnDelete();
            $table->string('period_label');
            $table->string('overall_status')->default('good');
            $table->decimal('score', 5, 2)->nullable();
            $table->text('notes')->nullable();
            $table->text('next_goal')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('progress_tracks');
    }
};
