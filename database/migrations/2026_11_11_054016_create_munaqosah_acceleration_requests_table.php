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
        Schema::create('munaqosah_acceleration_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('generus_id')->constrained('generus')->cascadeOnUpdate()->cascadeOnDelete();
            $table->foreignId('class_grade_id')->nullable()->constrained()->cascadeOnUpdate()->nullOnDelete();
            $table->foreignId('requested_by_id')->constrained('users')->cascadeOnUpdate()->cascadeOnDelete();
            $table->text('reason')->nullable();
            $table->string('status')->default('pending');
            $table->foreignId('decided_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('decided_at')->nullable();
            $table->text('decision_notes')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('munaqosah_acceleration_requests');
    }
};
