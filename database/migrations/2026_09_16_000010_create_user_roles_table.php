<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_roles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnUpdate()->restrictOnDelete();
            $table->foreignId('role_id')->constrained()->cascadeOnUpdate()->restrictOnDelete();
            $table->string('scope_type')->nullable();
            $table->unsignedBigInteger('scope_id')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['scope_type', 'scope_id']);
            $table->index('user_id');
            $table->index('role_id');
            $table->unique(['user_id', 'role_id', 'scope_type', 'scope_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_roles');
    }
};
