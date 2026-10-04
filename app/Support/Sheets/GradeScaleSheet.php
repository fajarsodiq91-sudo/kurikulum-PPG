<?php

namespace App\Support\Sheets;

use App\Models\GradeScale;

class GradeScaleSheet extends Sheet
{
    public function filename(): string
    {
        return 'konversi-nilai.xlsx';
    }

    public function headings(): array
    {
        return ['grade', 'min_score', 'max_score', 'description', 'sort_order', 'status'];
    }

    public function rows(): iterable
    {
        foreach (GradeScale::orderBy('sort_order')->orderByDesc('min_score')->cursor() as $scale) {
            yield [$scale->grade, $scale->min_score, $scale->max_score, $scale->description, $scale->sort_order, self::activeLabel($scale->is_active)];
        }
    }

    public function importRow(array $row): ?string
    {
        [$data, $error] = $this->validated($row, [
            'grade' => ['required', 'string', 'max:10'],
            'min_score' => ['required', 'integer', 'min:0', 'max:100'],
            'max_score' => ['required', 'integer', 'min:0', 'max:100', 'gte:min_score'],
            'description' => ['nullable', 'string'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'status' => ['nullable', 'string', 'max:50'],
        ]);

        if ($error !== null) {
            return $error;
        }

        GradeScale::updateOrCreate(['grade' => trim($data['grade'])], [
            'min_score' => (int) $data['min_score'],
            'max_score' => (int) $data['max_score'],
            'description' => $data['description'] ?? null,
            'sort_order' => (int) ($data['sort_order'] ?? 0),
            'is_active' => self::parseActive($data['status'] ?? 'aktif'),
        ]);

        return null;
    }
}
