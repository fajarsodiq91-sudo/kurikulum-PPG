<?php

namespace App\Exports;

use App\Models\LearningMaterial;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class LearningMaterialExport implements FromQuery, WithHeadings, WithMapping
{
    public function query(): Builder
    {
        return LearningMaterial::query()
            ->with(['materialChapter.materialCategory.classGrade', 'materialChapter.materialCategory.semester', 'academicYear'])
            ->orderBy('material_chapter_id')
            ->orderBy('sort_order');
    }

    public function headings(): array
    {
        return [
            'class_grade_code',
            'semester_code',
            'category_name',
            'category_code',
            'chapter_name',
            'chapter_code',
            'material_title',
            'material_code',
            'academic_year_code',
            'description',
            'is_active',
        ];
    }

    public function map($material): array
    {
        $category = $material->materialChapter->materialCategory;

        return [
            $category->classGrade?->code,
            $category->semester?->code,
            $category->name,
            $category->code,
            $material->materialChapter->name,
            $material->materialChapter->code,
            $material->title,
            $material->code,
            $material->academicYear?->code,
            $material->description,
            $material->is_active ? 1 : 0,
        ];
    }
}
