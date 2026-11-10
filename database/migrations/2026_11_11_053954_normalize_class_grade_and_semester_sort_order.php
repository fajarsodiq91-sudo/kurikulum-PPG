<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Gives every class_grades row within a level a consistent sort_order (numeric names
     * first and in numeric order, non-numeric names after in their existing order), every
     * level a sort_order matching the PPG curriculum progression (PAUD, Caberawit, Pra
     * Remaja, Remaja, Pra Nikah), and every semester a sort_order matching its "S1"/"S2"
     * code — needed so the kelas-KBM promotion feature can find "the next class" and
     * "semester 2" reliably; most rows were seeded with sort_order 0 and carried no real
     * ordering before this.
     */
    public function up(): void
    {
        $levelOrderByCode = ['PD' => 0, 'CB' => 1, 'PR' => 2, 'RM' => 3, 'PN' => 4];

        foreach ($levelOrderByCode as $code => $sortOrder) {
            DB::table('levels')->where('code', $code)->update(['sort_order' => $sortOrder]);
        }

        $levelIds = DB::table('class_grades')->distinct()->pluck('level_id');

        foreach ($levelIds as $levelId) {
            $grades = DB::table('class_grades')
                ->where('level_id', $levelId)
                ->orderBy('id')
                ->get(['id', 'name', 'sort_order']);

            // Numeric names (e.g. "7", "8", "9") sort by their numeric value; non-numeric
            // names (e.g. "Pra PAUD", "PAUD") keep whatever relative order their existing
            // sort_order already encoded, falling back to id when that was never set either.
            $ordered = $grades->sortBy(fn ($grade) => sprintf(
                '%d-%08d-%08d',
                ctype_digit((string) $grade->name) ? 0 : 1,
                ctype_digit((string) $grade->name) ? (int) $grade->name : (int) $grade->sort_order,
                $grade->id,
            ))->values();

            foreach ($ordered as $index => $grade) {
                DB::table('class_grades')->where('id', $grade->id)->update(['sort_order' => $index]);
            }
        }

        DB::table('semesters')->where('code', 'S1')->update(['sort_order' => 1]);
        DB::table('semesters')->where('code', 'S2')->update(['sort_order' => 2]);
    }

    /**
     * Not reversible: the original sort_order values (mostly 0) carried no information worth
     * restoring.
     */
    public function down(): void
    {
        //
    }
};
