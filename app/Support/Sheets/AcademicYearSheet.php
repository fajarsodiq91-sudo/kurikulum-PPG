<?php

namespace App\Support\Sheets;

use App\Models\AcademicYear;

class AcademicYearSheet extends Sheet
{
    public function filename(): string
    {
        return 'tahun-akademik.xlsx';
    }

    public function headings(): array
    {
        return ['name', 'code', 'start_year', 'end_year', 'status'];
    }

    public function rows(): iterable
    {
        foreach (AcademicYear::orderBy('start_year')->cursor() as $year) {
            yield [$year->name, $year->code, $year->start_year, $year->end_year, self::activeLabel($year->is_active)];
        }
    }

    public function importRow(array $row): ?string
    {
        [$data, $error] = $this->validated($row, [
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:50'],
            'start_year' => ['required', 'integer', 'digits:4'],
            'end_year' => ['required', 'integer', 'digits:4', 'gte:start_year'],
            'status' => ['nullable', 'string', 'max:50'],
        ]);

        if ($error !== null) {
            return $error;
        }

        AcademicYear::updateOrCreate(['code' => trim($data['code'])], [
            'name' => trim($data['name']),
            'start_year' => (int) $data['start_year'],
            'end_year' => (int) $data['end_year'],
            'is_active' => self::parseActive($data['status'] ?? 'aktif'),
        ]);

        return null;
    }
}
