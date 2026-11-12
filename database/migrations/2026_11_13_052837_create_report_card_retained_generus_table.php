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
        // Which generus a confirmed report_card_group_confirmation flagged as "tinggal
        // kelas" — absence here means "naik" (the default) once the confirmation is saved.
        Schema::create('report_card_retained_generus', function (Blueprint $table) {
            $table->id();
            $table->foreignId('report_card_group_confirmation_id')->constrained(indexName: 'rcrg_confirmation_id_foreign')->cascadeOnUpdate()->cascadeOnDelete();
            $table->foreignId('generus_id')->constrained('generus')->cascadeOnUpdate()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['report_card_group_confirmation_id', 'generus_id'], 'report_card_retained_generus_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('report_card_retained_generus');
    }
};
