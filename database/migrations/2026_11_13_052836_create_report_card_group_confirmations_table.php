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
        // A previous deploy of this same migration failed partway through (MySQL rejected an
        // auto-generated foreign key name as too long), leaving an incomplete table with no
        // constraints and nothing recorded in the migrations table. Safe to drop and recreate:
        // the feature never shipped successfully, so the table never held real data.
        Schema::dropIfExists('report_card_group_confirmations');

        Schema::create('report_card_group_confirmations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('report_card_publication_id')->constrained(indexName: 'rcgc_publication_id_foreign')->cascadeOnUpdate()->cascadeOnDelete();
            $table->foreignId('group_id')->constrained()->cascadeOnUpdate()->cascadeOnDelete();
            $table->string('status')->default('pending');
            $table->foreignId('confirmed_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamps();

            $table->unique(['report_card_publication_id', 'group_id'], 'rcgc_publication_group_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('report_card_group_confirmations');
    }
};
