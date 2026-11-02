<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->dropIndexCovering(['level_id', 'academic_year_id', 'semester_id', 'week_number']);

        Schema::table('default_schedules', function (Blueprint $table) {
            $table->dropColumn(['week_number', 'scheduled_date', 'day_of_week']);
            $table->json('days_of_week')->after('semester_id');
            $table->date('start_date')->after('days_of_week');
            $table->date('end_date')->after('start_date');
            $table->unique(['level_id', 'academic_year_id', 'semester_id']);
        });
    }

    public function down(): void
    {
        $this->dropIndexCovering(['level_id', 'academic_year_id', 'semester_id']);

        Schema::table('default_schedules', function (Blueprint $table) {
            $table->dropColumn(['days_of_week', 'start_date', 'end_date']);
            $table->unsignedSmallInteger('week_number')->after('semester_id');
            $table->date('scheduled_date')->nullable()->after('week_number');
            $table->unsignedTinyInteger('day_of_week')->nullable()->after('scheduled_date');
            $table->unique(['level_id', 'academic_year_id', 'semester_id', 'week_number']);
        });
    }

    /**
     * Drop whichever unique index actually covers these columns, by its real name — the name
     * Laravel's own column-based guess produces (as used by Blueprint::dropUnique($columns))
     * can exceed MySQL's 64-character identifier limit and silently not match what the
     * database truly stored the index as, so it is looked up instead of guessed.
     *
     * @param  list<string>  $columns
     */
    private function dropIndexCovering(array $columns): void
    {
        $index = collect(Schema::getIndexes('default_schedules'))
            ->first(fn (array $index): bool => $index['unique'] && $index['columns'] === $columns);

        if ($index !== null) {
            Schema::table('default_schedules', fn (Blueprint $table) => $table->dropUnique($index['name']));
        }
    }
};
