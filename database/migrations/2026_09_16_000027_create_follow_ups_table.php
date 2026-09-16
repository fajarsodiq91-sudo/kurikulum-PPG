<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('follow_ups', function (Blueprint $table) {
            $table->id();
            $table->foreignId('generus_id')->constrained('generus')->cascadeOnUpdate()->restrictOnDelete();
            $table->foreignId('teacher_id')->constrained('teachers')->cascadeOnUpdate()->restrictOnDelete();
            $table->string('title');
            $table->string('priority')->default('medium');
            $table->date('follow_up_date');
            $table->string('status')->default('scheduled');
            $table->text('notes')->nullable();
            $table->text('next_action')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('follow_ups');
    }
};
