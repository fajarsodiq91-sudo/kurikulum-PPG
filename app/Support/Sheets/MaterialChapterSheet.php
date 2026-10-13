<?php

namespace App\Support\Sheets;

use App\Models\ClassGrade;
use App\Models\MaterialCategory;
use App\Models\MaterialChapter;
use App\Models\Semester;

class MaterialChapterSheet extends Sheet
{
    public function filename(): string
    {
        return 'bab-materi.xlsx';
    }

    public function headings(): array
    {
        return ['class_grade_code', 'semester_code', 'category_code', 'name', 'code', 'sort_order', 'status', 'description'];
    }

    public function rows(): iterable
    {
        foreach (MaterialChapter::with(['materialCategory.classGrade', 'materialCategory.semester'])->orderBy('material_category_id')->orderBy('sort_order')->cursor() as $chapter) {
            $category = $chapter->materialCategory;

            yield [
                $category?->classGrade?->code,
                $category?->semester?->code,
                $category?->code,
                $chapter->name,
                $chapter->code,
                $chapter->sort_order,
                self::activeLabel($chapter->is_active),
                $chapter->description,
            ];
        }
    }

    public function importRow(array $row): ?string
    {
        [$data, $error] = $this->validated($row, [
            'class_grade_code' => ['required', 'string', 'max:50'],
            'semester_code' => ['required', 'string', 'max:50'],
            'category_code' => ['required', 'string', 'max:50'],
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:50'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'status' => ['nullable', 'string', 'max:50'],
            'description' => ['nullable', 'string'],
        ]);

        if ($error !== null) {
            return $error;
        }

        $classGrade = ClassGrade::where('code', trim($data['class_grade_code']))->where('is_active', true)->first();
        $semester = Semester::where('code', trim($data['semester_code']))->where('is_active', true)->first();

        if ($classGrade === null || $semester === null) {
            return 'Kode kelas atau semester tidak ditemukan atau tidak aktif.';
        }

        $category = MaterialCategory::where('class_grade_id', $classGrade->id)
            ->where('semester_id', $semester->id)
            ->where('code', trim($data['category_code']))
            ->first();

        if ($category === null) {
            return 'Kategori materi tidak ditemukan untuk kelas, semester, dan kode tersebut.';
        }

        MaterialChapter::updateOrCreate(
            ['material_category_id' => $category->id, 'code' => trim($data['code'])],
            [
                'name' => trim($data['name']),
                'sort_order' => (int) ($data['sort_order'] ?? 0),
                'is_active' => self::parseActive($data['status'] ?? 'aktif'),
                'description' => $data['description'] ?? null,
            ],
        );

        return null;
    }
}
