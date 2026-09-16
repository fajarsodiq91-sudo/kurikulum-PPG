<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('activity_executions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('activity_schedule_id')->constrained('activity_schedules')->cascadeOnUpdate()->restrictOnDelete();
            $table->date('actual_date');
            $table->string('status')->default('completed');
            $table->unsignedInteger('attendance_count')->default(0);
            $table->text('outcome');
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activity_executions');
    }
};
