<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('teachers', function (Blueprint $table) {
            $table->foreignId('region_id')->nullable()->after('email')->constrained()->cascadeOnUpdate()->nullOnDelete();
            $table->foreignId('village_id')->nullable()->after('region_id')->constrained()->cascadeOnUpdate()->nullOnDelete();
            $table->foreignId('group_id')->nullable()->after('village_id')->constrained()->cascadeOnUpdate()->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('teachers', function (Blueprint $table) {
            $table->dropConstrainedForeignId('group_id');
            $table->dropConstrainedForeignId('village_id');
            $table->dropConstrainedForeignId('region_id');
        });
    }
};
