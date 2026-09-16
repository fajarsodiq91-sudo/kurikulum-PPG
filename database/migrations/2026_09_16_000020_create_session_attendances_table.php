<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('session_attendances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('learning_session_id')->constrained('learning_sessions')->cascadeOnUpdate()->restrictOnDelete();
            $table->foreignId('generus_id')->constrained('generus')->cascadeOnUpdate()->restrictOnDelete();
            $table->string('status')->default('present');
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('session_attendances');
    }
};
