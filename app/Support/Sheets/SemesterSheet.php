<?php

namespace App\Support\Sheets;

use App\Models\AcademicYear;
use App\Models\Semester;

class SemesterSheet extends Sheet
{
    public function filename(): string
    {
        return 'semester.xlsx';
    }

    public function headings(): array
    {
        return ['academic_year_code', 'name', 'code', 'sort_order', 'status'];
    }

    public function rows(): iterable
    {
        foreach (Semester::with('academicYear')->orderBy('sort_order')->cursor() as $semester) {
            yield [$semester->academicYear?->code, $semester->name, $semester->code, $semester->sort_order, self::activeLabel($semester->is_active)];
        }
    }

    public function importRow(array $row): ?string
    {
        [$data, $error] = $this->validated($row, [
            'academic_year_code' => ['required', 'string', 'max:50'],
            'name' => ['required', 'string', 'max:255'],
            'code' => ['nullable', 'string', 'max:50'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'status' => ['nullable', 'string', 'max:50'],
        ]);

        if ($error !== null) {
            return $error;
        }

        $year = AcademicYear::where('code', trim($data['academic_year_code']))->first();

        if ($year === null) {
            return "Tahun akademik dengan kode \"{$data['academic_year_code']}\" tidak ditemukan.";
        }

        $semester = Semester::where('academic_year_id', $year->id)->whereRaw('lower(name) = ?', [mb_strtolower(trim($data['name']))])->first()
            ?? new Semester(['academic_year_id' => $year->id]);

        $semester->fill([
            'name' => trim($data['name']),
            'code' => $data['code'] ?? null,
            'sort_order' => (int) ($data['sort_order'] ?? 0),
            'is_active' => self::parseActive($data['status'] ?? 'aktif'),
        ])->save();

        return null;
    }
}
