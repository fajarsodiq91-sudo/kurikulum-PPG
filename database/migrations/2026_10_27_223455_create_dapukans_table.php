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
        Schema::create('dapukans', function (Blueprint $table) {
            $table->id();
            $table->string('role_slug');
            $table->string('group_label')->nullable();
            $table->string('name');
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('can_approve')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index('role_slug');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('dapukans');
    }
};
