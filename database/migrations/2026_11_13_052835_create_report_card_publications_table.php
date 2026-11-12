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
        Schema::create('report_card_publications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('academic_year_id')->constrained()->cascadeOnUpdate()->cascadeOnDelete();
            $table->foreignId('semester_id')->constrained()->cascadeOnUpdate()->cascadeOnDelete();
            $table->boolean('requires_group_confirmation')->default(false);
            $table->string('status')->default('pending_group_confirmation');
            $table->foreignId('initiated_by_id')->constrained('users')->cascadeOnUpdate()->cascadeOnDelete();
            $table->timestamp('published_at')->nullable();
            $table->timestamps();

            $table->unique(['academic_year_id', 'semester_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('report_card_publications');
    }
};
