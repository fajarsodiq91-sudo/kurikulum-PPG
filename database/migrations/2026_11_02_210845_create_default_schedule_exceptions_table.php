<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('default_schedule_exceptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('default_schedule_id')->constrained()->cascadeOnDelete();
            $table->date('exception_date');
            $table->string('reason')->nullable();
            $table->timestamps();

            $table->unique(['default_schedule_id', 'exception_date'], 'default_schedule_exceptions_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('default_schedule_exceptions');
    }
};
