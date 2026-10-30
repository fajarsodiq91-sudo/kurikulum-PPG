<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * "Pra PAUD" and "PAUD" share the same sort_order (0) under the PAUD level, so they fall
     * back to id order (PAUD first). The dashboard's kelas KBM resume table needs Pra PAUD to
     * appear before PAUD, so give them distinct sort_order values.
     */
    public function up(): void
    {
        DB::table('class_grades')
            ->where('code', '00')
            ->whereIn('level_id', DB::table('levels')->where('code', 'PD')->pluck('id'))
            ->update(['sort_order' => 0]);

        DB::table('class_grades')
            ->where('code', '0')
            ->whereIn('level_id', DB::table('levels')->where('code', 'PD')->pluck('id'))
            ->update(['sort_order' => 1]);
    }

    public function down(): void
    {
        DB::table('class_grades')
            ->whereIn('code', ['0', '00'])
            ->whereIn('level_id', DB::table('levels')->where('code', 'PD')->pluck('id'))
            ->update(['sort_order' => 0]);
    }
};
