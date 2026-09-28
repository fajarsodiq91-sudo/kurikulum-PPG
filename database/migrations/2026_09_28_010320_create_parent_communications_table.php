<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('parent_communications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('generus_id')->constrained('generus')->cascadeOnUpdate()->cascadeOnDelete();
            $table->foreignId('guardian_id')->nullable()->constrained('guardians')->cascadeOnUpdate()->nullOnDelete();
            $table->foreignId('teacher_id')->nullable()->constrained('teachers')->cascadeOnUpdate()->nullOnDelete();
            $table->string('channel');
            $table->date('communicated_at');
            $table->string('subject')->nullable();
            $table->text('message');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('parent_communications');
    }
};
