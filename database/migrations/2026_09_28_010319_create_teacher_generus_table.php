<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('teacher_generus', function (Blueprint $table) {
            $table->id();
            $table->foreignId('teacher_id')->constrained('teachers')->cascadeOnUpdate()->cascadeOnDelete();
            $table->foreignId('generus_id')->constrained('generus')->cascadeOnUpdate()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['teacher_id', 'generus_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('teacher_generus');
    }
};
