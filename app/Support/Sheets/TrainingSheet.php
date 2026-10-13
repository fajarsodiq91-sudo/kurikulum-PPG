<?php

namespace App\Support\Sheets;

use App\Models\AcademicYear;
use App\Models\Training;

class TrainingSheet extends Sheet
{
    public function filename(): string
    {
        return 'pelatihan-guru.xlsx';
    }

    public function headings(): array
    {
        return ['title', 'type', 'academic_year_code', 'scheduled_at', 'location', 'status', 'notes'];
    }

    public function rows(): iterable
    {
        foreach (Training::with('academicYear')->orderBy('id')->cursor() as $training) {
            yield [
                $training->title,
                $training->type,
                $training->academicYear?->code,
                $training->scheduled_at,
                $training->location,
                $training->status,
                $training->notes,
            ];
        }
    }

    public function importRow(array $row): ?string
    {
        [$data, $error] = $this->validated($row, [
            'title' => ['required', 'string', 'max:255'],
            'type' => ['required', 'string', 'max:50'],
            'academic_year_code' => ['required', 'string', 'max:50'],
            'scheduled_at' => ['nullable', 'date'],
            'location' => ['nullable', 'string', 'max:255'],
            'status' => ['required', 'string', 'max:50'],
            'notes' => ['nullable', 'string'],
        ]);

        if ($error !== null) {
            return $error;
        }

        $academicYear = AcademicYear::where('code', trim($data['academic_year_code']))->where('is_active', true)->first();

        if ($academicYear === null) {
            return 'Kode tahun ajaran tidak ditemukan atau tidak aktif.';
        }

        Training::updateOrCreate(
            ['title' => trim($data['title']), 'type' => trim($data['type']), 'academic_year_id' => $academicYear->id],
            [
                'scheduled_at' => $data['scheduled_at'] ?? null,
                'location' => $data['location'] ?? null,
                'status' => trim($data['status']),
                'notes' => $data['notes'] ?? null,
            ],
        );

        return null;
    }
}
