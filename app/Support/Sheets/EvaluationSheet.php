<?php

namespace App\Support\Sheets;

use App\Models\AcademicYear;
use App\Models\Evaluation;
use App\Models\LearningMaterial;
use App\Models\Semester;
use App\Models\Teacher;

class EvaluationSheet extends Sheet
{
    public function filename(): string
    {
        return 'evaluasi.xlsx';
    }

    public function headings(): array
    {
        return ['teacher_registration_number', 'material_code', 'semester_code', 'academic_year_code', 'title', 'type', 'scheduled_at', 'status', 'notes'];
    }

    public function rows(): iterable
    {
        foreach (Evaluation::with(['teacher', 'material', 'semester', 'academicYear'])->orderBy('id')->cursor() as $evaluation) {
            yield [
                $evaluation->teacher?->registration_number,
                $evaluation->material?->code,
                $evaluation->semester?->code,
                $evaluation->academicYear?->code,
                $evaluation->title,
                $evaluation->type,
                $evaluation->scheduled_at,
                $evaluation->status,
                $evaluation->notes,
            ];
        }
    }

    public function importRow(array $row): ?string
    {
        [$data, $error] = $this->validated($row, [
            'teacher_registration_number' => ['required', 'string', 'max:50'],
            'material_code' => ['required', 'string', 'max:50'],
            'semester_code' => ['required', 'string', 'max:50'],
            'academic_year_code' => ['required', 'string', 'max:50'],
            'title' => ['required', 'string', 'max:255'],
            'type' => ['required', 'string', 'max:50'],
            'scheduled_at' => ['nullable', 'date'],
            'status' => ['required', 'string', 'max:50'],
            'notes' => ['nullable', 'string'],
        ]);

        if ($error !== null) {
            return $error;
        }

        $teacher = Teacher::where('registration_number', trim($data['teacher_registration_number']))->first();
        $material = LearningMaterial::where('code', trim($data['material_code']))->first();
        $academicYear = AcademicYear::where('code', trim($data['academic_year_code']))->where('is_active', true)->first();
        $semester = $academicYear === null ? null : Semester::where('academic_year_id', $academicYear->id)
            ->where('code', trim($data['semester_code']))->where('is_active', true)->first();

        if ($teacher === null || $material === null || $semester === null || $academicYear === null) {
            return 'Guru, materi, semester, atau tahun ajaran tidak ditemukan atau tidak aktif.';
        }

        Evaluation::updateOrCreate(
            [
                'teacher_id' => $teacher->id,
                'material_id' => $material->id,
                'semester_id' => $semester->id,
                'academic_year_id' => $academicYear->id,
                'title' => trim($data['title']),
                'type' => trim($data['type']),
            ],
            [
                'scheduled_at' => $data['scheduled_at'] ?? null,
                'status' => trim($data['status']),
                'notes' => $data['notes'] ?? null,
            ],
        );

        return null;
    }
}
