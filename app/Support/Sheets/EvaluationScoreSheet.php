<?php

namespace App\Support\Sheets;

use App\Models\AcademicYear;
use App\Models\Evaluation;
use App\Models\EvaluationScore;
use App\Models\Generus;
use App\Models\LearningMaterial;
use App\Models\Semester;
use App\Models\Teacher;
use App\Models\User;
use App\Support\StudentScope;

class EvaluationScoreSheet extends Sheet
{
    public function __construct(private ?User $user = null) {}

    public function filename(): string
    {
        return 'nilai-evaluasi.xlsx';
    }

    public function headings(): array
    {
        return [
            'evaluation_teacher_registration_number', 'evaluation_material_code', 'evaluation_semester_code',
            'evaluation_academic_year_code', 'evaluation_title', 'evaluation_type',
            'generus_registration_number', 'score', 'grade', 'notes',
        ];
    }

    public function rows(): iterable
    {
        $ids = StudentScope::ids($this->user, 'manage-evaluations');

        foreach (StudentScope::limit(EvaluationScore::with(['evaluation.teacher', 'evaluation.material', 'evaluation.semester', 'evaluation.academicYear', 'generus']), $ids, 'generus_id')->orderBy('id')->cursor() as $score) {
            $evaluation = $score->evaluation;

            yield [
                $evaluation?->teacher?->registration_number,
                $evaluation?->material?->code,
                $evaluation?->semester?->code,
                $evaluation?->academicYear?->code,
                $evaluation?->title,
                $evaluation?->type,
                $score->generus?->registration_number,
                $score->score,
                $score->grade,
                $score->notes,
            ];
        }
    }

    public function importRow(array $row): ?string
    {
        [$data, $error] = $this->validated($row, [
            'evaluation_teacher_registration_number' => ['required', 'string', 'max:50'],
            'evaluation_material_code' => ['required', 'string', 'max:50'],
            'evaluation_semester_code' => ['required', 'string', 'max:50'],
            'evaluation_academic_year_code' => ['required', 'string', 'max:50'],
            'evaluation_title' => ['required', 'string', 'max:255'],
            'evaluation_type' => ['required', 'string', 'max:50'],
            'generus_registration_number' => ['required', 'string', 'max:50'],
            'score' => ['required', 'numeric', 'min:0', 'max:100'],
            'grade' => ['required', 'string', 'max:10'],
            'notes' => ['nullable', 'string'],
        ]);

        if ($error !== null) {
            return $error;
        }

        $teacher = Teacher::where('registration_number', trim($data['evaluation_teacher_registration_number']))->first();
        $material = LearningMaterial::where('code', trim($data['evaluation_material_code']))->first();
        $academicYear = AcademicYear::where('code', trim($data['evaluation_academic_year_code']))->first();
        $semester = $academicYear === null ? null : Semester::where('academic_year_id', $academicYear->id)
            ->where('code', trim($data['evaluation_semester_code']))->first();

        $evaluation = $teacher && $material && $semester && $academicYear
            ? Evaluation::where('teacher_id', $teacher->id)
                ->where('material_id', $material->id)
                ->where('semester_id', $semester->id)
                ->where('academic_year_id', $academicYear->id)
                ->where('title', trim($data['evaluation_title']))
                ->where('type', trim($data['evaluation_type']))
                ->first()
            : null;

        if ($evaluation === null) {
            return 'Evaluasi tidak ditemukan untuk kombinasi guru, materi, semester, tahun ajaran, judul, dan tipe tersebut.';
        }

        $generus = Generus::where('registration_number', trim($data['generus_registration_number']))->first();

        if ($generus === null) {
            return 'Generus dengan nomor registrasi tersebut tidak ditemukan.';
        }

        $allowedIds = StudentScope::ids($this->user, 'manage-evaluations');

        if ($allowedIds !== null && ! in_array($generus->id, $allowedIds, true)) {
            return 'Generus berada di luar wilayah akses Anda.';
        }

        EvaluationScore::updateOrCreate(
            ['evaluation_id' => $evaluation->id, 'generus_id' => $generus->id],
            ['score' => $data['score'], 'grade' => trim($data['grade']), 'notes' => $data['notes'] ?? null],
        );

        return null;
    }
}
