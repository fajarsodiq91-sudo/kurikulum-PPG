<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('generus_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('generus_id')->constrained('generus')->cascadeOnUpdate()->restrictOnDelete();
            $table->foreignId('region_id')->nullable()->constrained()->cascadeOnUpdate()->nullOnDelete();
            $table->foreignId('village_id')->nullable()->constrained()->cascadeOnUpdate()->nullOnDelete();
            $table->foreignId('group_id')->nullable()->constrained()->cascadeOnUpdate()->nullOnDelete();
            $table->foreignId('level_id')->nullable()->constrained()->cascadeOnUpdate()->nullOnDelete();
            $table->foreignId('academic_year_id')->nullable()->constrained()->cascadeOnUpdate()->nullOnDelete();
            $table->string('status')->default('active');
            $table->date('assigned_at')->nullable();
            $table->date('ended_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['generus_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('generus_assignments');
    }
};
