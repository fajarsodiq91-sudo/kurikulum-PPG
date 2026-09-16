<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('generus', function (Blueprint $table) {
            $table->id();
            $table->string('registration_number')->unique();
            $table->string('full_name');
            $table->string('gender')->nullable();
            $table->date('birth_date')->nullable();
            $table->string('photo')->nullable();
            $table->string('status')->default('active');
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('generus');
    }
};
