<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('learning_sessions', function (Blueprint $table) {
            $table->string('level')->nullable()->after('material_id');
            $table->foreignId('village_id')->nullable()->after('level')->constrained()->cascadeOnUpdate()->nullOnDelete();
            $table->foreignId('group_id')->nullable()->after('village_id')->constrained()->cascadeOnUpdate()->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('learning_sessions', function (Blueprint $table) {
            $table->dropConstrainedForeignId('group_id');
            $table->dropConstrainedForeignId('village_id');
            $table->dropColumn('level');
        });
    }
};
