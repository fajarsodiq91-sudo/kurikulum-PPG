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
            $table->string('status')->default('draft')->after('is_done');
            $table->timestamp('sent_at')->nullable()->after('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('notulens', function (Blueprint $table) {
            $table->dropColumn(['status', 'sent_at']);
        });
    }
};
