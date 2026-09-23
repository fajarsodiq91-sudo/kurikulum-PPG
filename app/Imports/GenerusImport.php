<?php

namespace App\Imports;

use App\Models\AcademicYear;
use App\Models\Generus;
use App\Models\GenerusAssignment;
use App\Models\Group;
use App\Models\Level;
use App\Models\Region;
use App\Models\User;
use App\Models\Village;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Maatwebsite\Excel\Concerns\OnEachRow;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Row;

class GenerusImport implements OnEachRow, WithChunkReading, WithHeadingRow
{
    /**
     * @var array<string, string>
     */
    private array $errors = [];

    /**
     * @param  User|null  $user  Importing user whose role scope limits the rows; null imports without scope checks.
     */
    public function __construct(private ?User $user = null) {}

    public function onRow(Row $row): void
    {
        $data = $row->toArray();

        if (collect($data)->filter(fn (mixed $value): bool => filled($value))->isEmpty()) {
            return;
        }

        $data = collect($data)
            ->map(fn (mixed $value): mixed => is_int($value) || is_float($value) ? (string) $value : $value)
            ->all();

        $validator = Validator::make($data, [
            'registration_number' => ['required', 'string', 'max:50'],
            'record_number' => ['nullable', 'string', 'max:50'],
            'full_name' => ['required', 'string', 'max:255'],
            'school_name' => ['nullable', 'string', 'max:255'],
            'nis' => ['nullable', 'string', 'max:50'],
            'father_name' => ['nullable', 'string', 'max:255'],
            'mother_name' => ['nullable', 'string', 'max:255'],
            'father_occupation' => ['nullable', 'string', 'max:255'],
            'mother_occupation' => ['nullable', 'string', 'max:255'],
            'phone_number' => ['nullable', 'string', 'max:50'],
            'gender' => ['nullable', 'string', 'max:50'],
            'birth_place' => ['nullable', 'string', 'max:255'],
            'birth_date' => ['nullable', 'date'],
            'birth_order' => ['nullable', 'integer', 'min:1', 'max:32767'],
            'sibling_count' => ['nullable', 'integer', 'min:0', 'max:32767'],
            'school_grade' => ['nullable', 'string', 'max:50'],
            'learning_class' => ['nullable', 'string', 'max:100'],
            'educational_level' => ['nullable', 'string', 'max:100'],
            'status' => ['required', Rule::in(['active', 'pindah_sambung', 'married'])],
            'transfer_destination' => ['nullable', Rule::in(['internal', 'external'])],
            'notes' => ['nullable', 'string'],
            'region_code' => ['nullable', 'string', 'max:50'],
            'village_code' => ['nullable', 'string', 'max:50'],
            'group_code' => ['nullable', 'string', 'max:50'],
            'level_code' => ['nullable', 'string', 'max:50'],
            'academic_year_code' => ['nullable', 'string', 'max:50'],
            'assignment_status' => ['nullable', 'string', 'max:50'],
            'assignment_notes' => ['nullable', 'string'],
        ]);

        if ($validator->fails()) {
            $this->addErrors($row->getIndex(), $validator->errors()->all());

            return;
        }

        $validated = $validator->validated();

        $assignmentFields = collect([
            'region_code',
            'village_code',
            'group_code',
            'level_code',
            'academic_year_code',
            'assignment_status',
        ]);
        $hasAssignment = $assignmentFields->contains(fn (string $field): bool => filled($validated[$field] ?? null));

        if (Generus::onlyTrashed()->where('registration_number', $validated['registration_number'])->exists()) {
            $this->addErrors($row->getIndex(), ['Generus dengan nomor registrasi ini sudah dihapus.']);

            return;
        }

        if ($this->isScopedUser()) {
            $existingGenerus = Generus::query()->where('registration_number', $validated['registration_number'])->first();

            if ($existingGenerus && ! Generus::visibleTo($this->user)->whereKey($existingGenerus->id)->exists()) {
                $this->addErrors($row->getIndex(), ['Generus dengan nomor registrasi ini berada di luar wilayah akses Anda.']);

                return;
            }

            if (! $existingGenerus && ! $hasAssignment) {
                $this->addErrors($row->getIndex(), ['Generus baru wajib memiliki penempatan di wilayah akses Anda.']);

                return;
            }
        }

        if (! $hasAssignment) {
            $this->upsertGenerus($validated);

            return;
        }

        if ($assignmentFields->contains(fn (string $field): bool => blank($validated[$field] ?? null))) {
            $this->addErrors($row->getIndex(), ['Kolom penempatan harus diisi lengkap atau seluruhnya dikosongkan.']);

            return;
        }

        $region = Region::query()->where('code', $validated['region_code'])->where('is_active', true)->first();
        $village = Village::query()->where('code', $validated['village_code'])->where('region_id', $region?->id)->where('is_active', true)->first();
        $group = Group::query()->where('code', $validated['group_code'])->where('village_id', $village?->id)->where('is_active', true)->first();
        $level = Level::query()->where('code', $validated['level_code'])->where('is_active', true)->first();
        $academicYear = AcademicYear::query()->where('code', $validated['academic_year_code'])->where('is_active', true)->first();

        if (! $region || ! $village || ! $group || ! $level || ! $academicYear) {
            $this->addErrors($row->getIndex(), ['Kode wilayah, jenjang, atau tahun akademik tidak ditemukan atau tidak aktif.']);

            return;
        }

        if ($this->isScopedUser() && ! $this->user->coversPlacement(Generus::MANAGE_PERMISSION, $region->id, $village->id, $group->id)) {
            $this->addErrors($row->getIndex(), ['Penempatan berada di luar wilayah akses Anda.']);

            return;
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

    private function isScopedUser(): bool
    {
        return $this->user !== null && ! $this->user->hasGlobalAccess(Generus::MANAGE_PERMISSION);
    }

    /**
     * Validation errors keyed by spreadsheet row, e.g. `row_5`.
     *
     * @return array<string, string>
     */
    public function errors(): array
    {
        return $this->errors;
    }

    /**
     * @param  list<string>  $messages
     */
    private function addErrors(int $rowIndex, array $messages): void
    {
        $this->errors["row_{$rowIndex}"] = "Baris {$rowIndex}: ".implode(' ', $messages);
    }

    public function chunkSize(): int
    {
        return 250;
    }
}
