<?php

namespace App\Imports;

use App\Models\AcademicYear;
use App\Models\Generus;
use App\Models\GenerusAssignment;
use App\Models\Group;
use App\Models\Level;
use App\Models\Region;
use App\Models\Village;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Maatwebsite\Excel\Concerns\OnEachRow;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Row;

class GenerusImport implements OnEachRow, WithChunkReading, WithHeadingRow
{
    public function onRow(Row $row): void
    {
        $data = $row->toArray();

        if (collect($data)->filter(fn (mixed $value): bool => filled($value))->isEmpty()) {
            return;
        }

        $validated = Validator::make($data, [
            'registration_number' => ['required', 'string', 'max:50'],
            'full_name' => ['required', 'string', 'max:255'],
            'gender' => ['nullable', 'string', 'max:50'],
            'birth_date' => ['nullable', 'date'],
            'birth_order' => ['nullable', 'integer', 'min:1', 'max:32767'],
            'sibling_count' => ['nullable', 'integer', 'min:0', 'max:32767'],
            'status' => ['nullable', 'string', 'max:50'],
            'transfer_destination' => ['nullable', 'string', 'max:50'],
            'region_code' => ['nullable', 'string', 'max:50'],
            'village_code' => ['nullable', 'string', 'max:50'],
            'group_code' => ['nullable', 'string', 'max:50'],
            'level_code' => ['nullable', 'string', 'max:50'],
            'academic_year_code' => ['nullable', 'string', 'max:50'],
            'assignment_status' => ['nullable', 'string', 'max:50'],
        ])->validate();

        $assignmentFields = collect([
            'region_code',
            'village_code',
            'group_code',
            'level_code',
            'academic_year_code',
            'assignment_status',
        ]);
        $hasAssignment = $assignmentFields->contains(fn (string $field): bool => filled($validated[$field] ?? null));

        if (! $hasAssignment) {
            $this->upsertGenerus($validated);

            return;
        }

        if ($assignmentFields->contains(fn (string $field): bool => blank($validated[$field] ?? null))) {
            throw ValidationException::withMessages([
                "row_{$row->getIndex()}" => 'Kolom penempatan harus diisi lengkap atau seluruhnya dikosongkan.',
            ]);
        }

        $region = Region::query()->where('code', $validated['region_code'])->where('is_active', true)->first();
        $village = Village::query()->where('code', $validated['village_code'])->where('region_id', $region?->id)->where('is_active', true)->first();
        $group = Group::query()->where('code', $validated['group_code'])->where('village_id', $village?->id)->where('is_active', true)->first();
        $level = Level::query()->where('code', $validated['level_code'])->where('is_active', true)->first();
        $academicYear = AcademicYear::query()->where('code', $validated['academic_year_code'])->where('is_active', true)->first();

        if (! $region || ! $village || ! $group || ! $level || ! $academicYear) {
            throw ValidationException::withMessages([
                "row_{$row->getIndex()}" => 'Kode wilayah, jenjang, atau tahun akademik tidak ditemukan atau tidak aktif.',
            ]);
        }

        DB::transaction(function () use ($validated, $region, $village, $group, $level, $academicYear): void {
            $generus = $this->upsertGenerus($validated);

            GenerusAssignment::updateOrCreate(
                [
                    'generus_id' => $generus->id,
                    'academic_year_id' => $academicYear->id,
                ],
                [
                    'region_id' => $region->id,
                    'village_id' => $village->id,
                    'group_id' => $group->id,
                    'level_id' => $level->id,
                    'status' => $validated['assignment_status'],
                    'assigned_at' => now()->toDateString(),
                    'notes' => $validated['assignment_notes'] ?? null,
                ],
            );
        });
    }

    /**
     * @param  array<string, mixed>  $validated
     */
    private function upsertGenerus(array $validated): Generus
    {
        return Generus::updateOrCreate(
            ['registration_number' => $validated['registration_number']],
            collect($validated)->only([
                'record_number',
                'full_name',
                'school_name',
                'nis',
                'father_name',
                'mother_name',
                'father_occupation',
                'mother_occupation',
                'phone_number',
                'gender',
                'birth_place',
                'birth_date',
                'birth_order',
                'sibling_count',
                'school_grade',
                'learning_class',
                'educational_level',
                'status',
                'transfer_destination',
                'notes',
            ])->all(),
        );
    }

    public function chunkSize(): int
    {
        return 250;
    }
}
