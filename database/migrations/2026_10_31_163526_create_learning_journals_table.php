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
        Schema::create('learning_journals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('learning_session_id')->unique()->constrained()->cascadeOnDelete();
            $table->foreignId('material_id')->nullable()->constrained('learning_materials')->nullOnDelete();
            $table->text('content')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('learning_journals');
    }
};
