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
        Schema::table('notulens', function (Blueprint $table) {
            $table->foreignId('musyawaroh_id')->nullable()->after('id')->constrained()->cascadeOnUpdate()->cascadeOnDelete();
            $table->boolean('is_done')->default(false)->after('bucket');
            $table->foreignId('checked_by_id')->nullable()->after('is_done')->constrained('users')->cascadeOnUpdate()->nullOnDelete();
            $table->timestamp('checked_at')->nullable()->after('checked_by_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('notulens', function (Blueprint $table) {
            $table->dropConstrainedForeignId('musyawaroh_id');
            $table->dropConstrainedForeignId('checked_by_id');
            $table->dropColumn(['is_done', 'checked_at']);
        });
    }
};
