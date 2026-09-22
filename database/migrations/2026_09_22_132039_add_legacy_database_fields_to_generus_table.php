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
        Schema::table('generus', function (Blueprint $table) {
            Schema::table('generus', function (Blueprint $table) {
                $table->string('record_number')->nullable()->after('registration_number');
                $table->string('school_name')->nullable()->after('full_name');
                $table->string('nis')->nullable()->after('school_name');
                $table->string('father_name')->nullable()->after('nis');
                $table->string('mother_name')->nullable()->after('father_name');
                $table->string('father_occupation')->nullable()->after('mother_name');
                $table->string('mother_occupation')->nullable()->after('father_occupation');
                $table->string('phone_number')->nullable()->after('mother_occupation');
                $table->string('birth_place')->nullable()->after('phone_number');
                $table->unsignedSmallInteger('birth_order')->nullable()->after('birth_date');
                $table->unsignedSmallInteger('sibling_count')->nullable()->after('birth_order');
                $table->string('school_grade')->nullable()->after('sibling_count');
                $table->string('learning_class')->nullable()->after('school_grade');
                $table->string('educational_level')->nullable()->after('learning_class');
            });
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('generus', function (Blueprint $table) {
            Schema::table('generus', function (Blueprint $table) {
                $table->dropColumn([
                    'record_number',
                    'school_name',
                    'nis',
                    'father_name',
                    'mother_name',
                    'father_occupation',
                    'mother_occupation',
                    'phone_number',
                    'birth_place',
                    'birth_order',
                    'sibling_count',
                    'school_grade',
                    'learning_class',
                    'educational_level',
                ]);
            });
        });
    }
};
