<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('teachers', function (Blueprint $table) {
            $table->string('rfid_uid')->nullable()->unique()->after('registration_number');
            $table->json('face_descriptor')->nullable()->after('photo');
        });

        Schema::table('generus', function (Blueprint $table) {
            $table->json('face_descriptor')->nullable()->after('photo');
        });
    }

    public function down(): void
    {
        Schema::table('generus', function (Blueprint $table) {
            $table->dropColumn('face_descriptor');
        });

        Schema::table('teachers', function (Blueprint $table) {
            $table->dropColumn(['face_descriptor', 'rfid_uid']);
        });
    }
};
