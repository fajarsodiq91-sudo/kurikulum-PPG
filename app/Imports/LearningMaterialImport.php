<?php

namespace App\Imports;

use App\Models\AcademicYear;
use App\Models\ClassGrade;
use App\Models\LearningMaterial;
use App\Models\MaterialCategory;
use App\Models\MaterialChapter;
use App\Models\Semester;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Maatwebsite\Excel\Concerns\OnEachRow;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Row;

class LearningMaterialImport implements OnEachRow, WithChunkReading, WithHeadingRow
{
    /**
     * @var array<string, string>
     */
    private array $errors = [];

    public function onRow(Row $row): void
    {
        $data = $row->toArray();

        if (collect($data)->filter(fn (mixed $value): bool => filled($value))->isEmpty()) {
            return;
        }

        $data = collect($data)
            ->map(fn (mixed $value): mixed => is_int($value) || is_float($value) ? (string) $value : $value)
            ->all();

        $rowIndex = $row->getIndex();

        $validator = Validator::make($data, [
            'class_grade_code' => ['required', 'string', 'max:50'],
            'semester_code' => ['required', 'string', 'max:50'],
            'category_name' => ['required', 'string', 'max:255'],
            'category_code' => ['required', 'string', 'max:50'],
            'chapter_name' => ['required', 'string', 'max:255'],
            'chapter_code' => ['required', 'string', 'max:50'],
            'material_title' => ['required', 'string', 'max:255'],
            'material_code' => ['required', 'string', 'max:50'],
            'academic_year_code' => ['nullable', 'string', 'max:50'],
            'description' => ['nullable', 'string'],
            'is_active' => ['required', 'boolean'],
        ]);

        if ($validator->fails()) {
            $this->addErrors($rowIndex, $validator->errors()->all());

            return;
        }

        $validated = $validator->validated();

        $classGrade = ClassGrade::query()->where('code', $validated['class_grade_code'])->where('is_active', true)->first();
        $semester = Semester::query()->where('code', $validated['semester_code'])->where('is_active', true)->first();

        if (! $classGrade || ! $semester) {
            $this->addErrors($rowIndex, ['Kode kelas atau semester tidak ditemukan atau tidak aktif.']);

            return;
        }

        $academicYear = null;

        if (filled($validated['academic_year_code'] ?? null)) {
            $academicYear = AcademicYear::query()->where('code', $validated['academic_year_code'])->where('is_active', true)->first();

            if (! $academicYear) {
                $this->addErrors($rowIndex, ['Kode tahun ajaran tidak ditemukan atau tidak aktif.']);

                return;
            }
        }

        DB::transaction(function () use ($validated, $classGrade, $semester, $academicYear): void {
            $category = MaterialCategory::query()->updateOrCreate(
                [
                    'class_grade_id' => $classGrade->id,
                    'semester_id' => $semester->id,
                    'code' => $validated['category_code'],
                ],
                ['name' => $validated['category_name']],
            );

            $chapter = MaterialChapter::query()->updateOrCreate(
                [
                    'material_category_id' => $category->id,
                    'code' => $validated['chapter_code'],
                ],
                ['name' => $validated['chapter_name']],
            );

            LearningMaterial::query()->updateOrCreate(
                ['code' => $validated['material_code']],
                [
                    'material_chapter_id' => $chapter->id,
                    'title' => $validated['material_title'],
                    'academic_year_id' => $academicYear?->id,
                    'description' => $validated['description'] ?? null,
                    'is_active' => $validated['is_active'],
                ],
            );
        });
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
