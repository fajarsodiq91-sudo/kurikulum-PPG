<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('generus', function (Blueprint $table) {
            $table->foreignId('school_grade_id')->nullable()->after('school_grade')->constrained('class_grades')->nullOnDelete();
            $table->foreignId('learning_class_id')->nullable()->after('learning_class')->constrained('class_grades')->nullOnDelete();
        });

        Schema::table('generus', function (Blueprint $table) {
            $table->dropColumn(['school_grade', 'learning_class', 'educational_level']);
        });
    }

    public function down(): void
    {
        Schema::table('generus', function (Blueprint $table) {
            $table->string('school_grade')->nullable()->after('sibling_count');
            $table->string('learning_class')->nullable()->after('school_grade');
            $table->string('educational_level')->nullable()->after('learning_class');
        });

        Schema::table('generus', function (Blueprint $table) {
            $table->dropConstrainedForeignId('school_grade_id');
            $table->dropConstrainedForeignId('learning_class_id');
        });
    }
};
