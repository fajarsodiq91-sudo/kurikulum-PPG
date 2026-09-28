<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('generus', function (Blueprint $table) {
            $table->string('rfid_uid')->nullable()->unique()->after('registration_number');
        });

        Schema::table('session_attendances', function (Blueprint $table) {
            $table->string('method')->default('manual')->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('session_attendances', function (Blueprint $table) {
            $table->dropColumn('method');
        });

        Schema::table('generus', function (Blueprint $table) {
            $table->dropColumn('rfid_uid');
        });
    }
};
