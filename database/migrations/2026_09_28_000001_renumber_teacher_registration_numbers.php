<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Teachers numbered in the generus format (YYMM0001) move to YYMM99001 so the two never collide.
     */
    public function up(): void
    {
        $sequences = [];

        DB::table('teachers')->orderBy('created_at')->orderBy('id')->get(['id', 'created_at', 'registration_number'])
            ->filter(fn (object $teacher): bool => preg_match('/^\d{8}$/', (string) $teacher->registration_number) === 1)
            ->each(function (object $teacher) use (&$sequences): void {
                $prefix = Carbon::parse($teacher->created_at ?? now())->format('ym').'99';
                $sequences[$prefix] = ($sequences[$prefix] ?? 0) + 1;

                DB::table('teachers')->where('id', $teacher->id)->update([
                    'registration_number' => $prefix.str_pad((string) $sequences[$prefix], 3, '0', STR_PAD_LEFT),
                ]);
            });
    }

    public function down(): void {}
};
