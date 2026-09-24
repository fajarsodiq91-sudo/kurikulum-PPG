<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('teachers', function (Blueprint $table) {
            $table->string('registration_number')->nullable()->unique()->after('id');
            $table->string('photo')->nullable()->after('email');
        });

        $this->backfillRegistrationNumbers();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('teachers', function (Blueprint $table) {
            $table->dropUnique(['registration_number']);
            $table->dropColumn(['registration_number', 'photo']);
        });
    }

    /**
     * Existing teachers get a YYMM0001 number based on the month they were created.
     */
    private function backfillRegistrationNumbers(): void
    {
        $sequences = [];

        DB::table('teachers')->orderBy('created_at')->orderBy('id')->get(['id', 'created_at'])
            ->each(function (object $teacher) use (&$sequences): void {
                $prefix = Carbon::parse($teacher->created_at ?? now())->format('ym');
                $sequences[$prefix] = ($sequences[$prefix] ?? 0) + 1;

                DB::table('teachers')->where('id', $teacher->id)->update([
                    'registration_number' => $prefix.str_pad((string) $sequences[$prefix], 4, '0', STR_PAD_LEFT),
                ]);
            });
    }
};
