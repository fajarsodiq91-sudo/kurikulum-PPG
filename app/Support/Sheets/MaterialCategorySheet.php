<?php

namespace App\Support\Sheets;

use App\Models\ClassGrade;
use App\Models\MaterialCategory;
use App\Models\Semester;

class MaterialCategorySheet extends Sheet
{
    public function filename(): string
    {
        return 'kategori-materi.xlsx';
    }

    public function headings(): array
    {
        return ['class_grade_code', 'semester_code', 'name', 'code', 'sort_order', 'status', 'description'];
    }

    public function rows(): iterable
    {
        foreach (MaterialCategory::with(['classGrade', 'semester'])->orderBy('class_grade_id')->orderBy('semester_id')->orderBy('sort_order')->cursor() as $category) {
            yield [
                $category->classGrade?->code,
                $category->semester?->code,
                $category->name,
                $category->code,
                $category->sort_order,
                self::activeLabel($category->is_active),
                $category->description,
            ];
        }
    }

    public function importRow(array $row): ?string
    {
        [$data, $error] = $this->validated($row, [
            'class_grade_code' => ['required', 'string', 'max:50'],
            'semester_code' => ['required', 'string', 'max:50'],
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

        MaterialCategory::updateOrCreate(
            ['class_grade_id' => $classGrade->id, 'semester_id' => $semester->id, 'code' => trim($data['code'])],
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
