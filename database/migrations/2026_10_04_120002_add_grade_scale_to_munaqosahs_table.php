<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('munaqosahs', function (Blueprint $table) {
            $table->foreignId('grade_scale_id')->nullable()->after('result')->constrained()->cascadeOnUpdate()->nullOnDelete();
            $table->text('grade_description')->nullable()->after('grade_scale_id');
        });
    }

    public function down(): void
    {
        Schema::table('munaqosahs', function (Blueprint $table) {
            $table->dropForeign(['grade_scale_id']);
            $table->dropColumn(['grade_scale_id', 'grade_description']);
        });
    }
};
