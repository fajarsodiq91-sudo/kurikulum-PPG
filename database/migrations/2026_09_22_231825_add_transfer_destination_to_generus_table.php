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
                $table->string('transfer_destination')->nullable()->after('status');
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
                $table->dropColumn('transfer_destination');
            });
        });
    }
};
