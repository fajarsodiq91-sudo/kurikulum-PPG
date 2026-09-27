<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('generus_assignments', function (Blueprint $table) {
            $table->dropIndex(['generus_id', 'status']);
            $table->dropConstrainedForeignId('academic_year_id');
            $table->dropColumn('status');
        });

        Schema::table('generus_assignments', function (Blueprint $table) {
            $table->index(['generus_id', 'ended_at']);
        });
    }

    public function down(): void
    {
        Schema::table('generus_assignments', function (Blueprint $table) {
            $table->dropIndex(['generus_id', 'ended_at']);
            $table->foreignId('academic_year_id')->nullable()->after('level_id')->constrained()->cascadeOnUpdate()->nullOnDelete();
            $table->string('status')->default('active')->after('academic_year_id');
        });

        Schema::table('generus_assignments', function (Blueprint $table) {
            $table->index(['generus_id', 'status']);
        });
    }
};
