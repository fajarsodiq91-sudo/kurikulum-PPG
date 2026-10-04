<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('learning_materials', function (Blueprint $table) {
            $table->dropForeign(['curriculum_program_id']);
            $table->dropForeign(['level_id']);
            $table->dropForeign(['semester_id']);
            $table->dropColumn(['curriculum_program_id', 'level_id', 'semester_id']);

            $table->foreignId('academic_year_id')->nullable()->change();
            $table->foreignId('material_chapter_id')->after('id')->constrained()->cascadeOnUpdate()->restrictOnDelete();
            $table->unsignedInteger('sort_order')->default(0)->after('code');
        });
    }

    public function down(): void
    {
        Schema::table('learning_materials', function (Blueprint $table) {
            $table->dropForeign(['material_chapter_id']);
            $table->dropColumn(['material_chapter_id', 'sort_order']);

            $table->foreignId('curriculum_program_id')->after('id')->constrained('curriculum_programs')->cascadeOnUpdate()->restrictOnDelete();
            $table->foreignId('level_id')->constrained()->cascadeOnUpdate()->restrictOnDelete();
            $table->foreignId('semester_id')->constrained()->cascadeOnUpdate()->restrictOnDelete();
            $table->foreignId('academic_year_id')->nullable(false)->change();
        });
    }
};
