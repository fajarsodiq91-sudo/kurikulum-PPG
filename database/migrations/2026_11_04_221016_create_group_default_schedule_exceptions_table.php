<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // A previous deploy of this migration failed after creating the table but before adding
        // its foreign key (whose Laravel-generated name exceeded MySQL's 64-character identifier
        // limit, since this migration wasn't recorded as run), so start from a clean slate.
        Schema::dropIfExists('group_default_schedule_exceptions');

        Schema::create('group_default_schedule_exceptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('group_default_schedule_id')->constrained(indexName: 'group_default_schedule_exceptions_schedule_id_fk')->cascadeOnDelete();
            $table->date('exception_date');
            $table->string('reason')->nullable();
            $table->timestamps();

            $table->unique(['group_default_schedule_id', 'exception_date'], 'group_default_schedule_exceptions_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('group_default_schedule_exceptions');
    }
};
