<?php

namespace App\Support\Sheets;

use App\Models\ClassGrade;
use App\Models\Level;

class ClassGradeSheet extends Sheet
{
    public function filename(): string
    {
        return 'kelas.xlsx';
    }

    public function headings(): array
    {
        return ['name', 'code', 'level_code', 'sort_order', 'status', 'description'];
    }

    public function rows(): iterable
    {
        foreach (ClassGrade::with('level')->orderBy('sort_order')->orderBy('name')->cursor() as $classGrade) {
            yield [
                $classGrade->name,
                $classGrade->code,
                $classGrade->level?->code,
                $classGrade->sort_order,
                self::activeLabel($classGrade->is_active),
                $classGrade->description,
            ];
        }
    }

    public function importRow(array $row): ?string
    {
        [$data, $error] = $this->validated($row, [
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:50'],
            'level_code' => ['required', 'string', 'max:50'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'status' => ['nullable', 'string', 'max:50'],
            'description' => ['nullable', 'string'],
        ]);

        if ($error !== null) {
            return $error;
        }

        $level = Level::where('code', trim($data['level_code']))->first();

        if ($level === null) {
            return "Jenjang dengan kode \"{$data['level_code']}\" tidak ditemukan.";
        }

        ClassGrade::updateOrCreate(['code' => trim($data['code'])], [
            'name' => trim($data['name']),
            'level_id' => $level->id,
            'sort_order' => (int) ($data['sort_order'] ?? 0),
            'is_active' => self::parseActive($data['status'] ?? 'aktif'),
            'description' => $data['description'] ?? null,
        ]);

        return null;
    }
}
