<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('email')->nullable()->change();
            $table->string('username')->nullable()->unique()->after('email');
            $table->foreignId('generus_id')->nullable()->after('username')->constrained('generus')->cascadeOnUpdate()->nullOnDelete();
            $table->foreignId('teacher_id')->nullable()->after('generus_id')->constrained('teachers')->cascadeOnUpdate()->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('teacher_id');
            $table->dropConstrainedForeignId('generus_id');
            $table->dropUnique(['username']);
            $table->dropColumn('username');
        });
    }
};
