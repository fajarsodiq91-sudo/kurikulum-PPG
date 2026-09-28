<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('generus_guardian', function (Blueprint $table) {
            $table->id();
            $table->foreignId('generus_id')->constrained('generus')->cascadeOnUpdate()->cascadeOnDelete();
            $table->foreignId('guardian_id')->constrained('guardians')->cascadeOnUpdate()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['generus_id', 'guardian_id']);
        });

        $this->backfillFromParentNames();
    }

    public function down(): void
    {
        Schema::dropIfExists('generus_guardian');
    }

    /**
     * Existing generus with a father or mother name get linked guardian records.
     */
    private function backfillFromParentNames(): void
    {
        DB::table('generus')->whereNull('deleted_at')->get(['id', 'father_name', 'mother_name'])
            ->each(function (object $generus): void {
                foreach ([['father_name', 'Ayah'], ['mother_name', 'Ibu']] as [$column, $relationship]) {
                    $name = trim((string) $generus->{$column});

                    if ($name === '') {
                        continue;
                    }

                    $guardianId = DB::table('guardians')
                        ->whereRaw('lower(full_name) = ?', [mb_strtolower($name)])
                        ->where('relationship', $relationship)
                        ->value('id') ?? DB::table('guardians')->insertGetId([
                            'full_name' => $name,
                            'relationship' => $relationship,
                            'status' => 'active',
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);

                    DB::table('generus_guardian')->insertOrIgnore([
                        'generus_id' => $generus->id,
                        'guardian_id' => $guardianId,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            });
    }
};
