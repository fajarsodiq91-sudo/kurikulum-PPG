<?php

namespace App\Support\Sheets;

use App\Models\AcademicYear;
use App\Models\Generus;
use App\Models\ProgressTrack;
use App\Models\Semester;
use App\Models\User;
use App\Support\StudentScope;

class ProgressTrackSheet extends Sheet
{
    public function __construct(private ?User $user = null) {}

    public function filename(): string
    {
        return 'progress-tracking.xlsx';
    }

    public function headings(): array
    {
        return ['generus_registration_number', 'academic_year_code', 'semester_code', 'period_label', 'overall_status', 'score', 'notes', 'next_goal'];
    }

    public function rows(): iterable
    {
        $ids = StudentScope::ids($this->user, 'manage-progress-tracking');

        foreach (StudentScope::limit(ProgressTrack::with(['generus', 'academicYear', 'semester']), $ids, 'generus_id')->orderBy('id')->cursor() as $track) {
            yield [
                $track->generus?->registration_number,
                $track->academicYear?->code,
                $track->semester?->code,
                $track->period_label,
                $track->overall_status,
                $track->score,
                $track->notes,
                $track->next_goal,
            ];
        }
    }

    public function importRow(array $row): ?string
    {
        [$data, $error] = $this->validated($row, [
            'generus_registration_number' => ['required', 'string', 'max:50'],
            'academic_year_code' => ['required', 'string', 'max:50'],
            'semester_code' => ['required', 'string', 'max:50'],
            'period_label' => ['required', 'string', 'max:50'],
            'overall_status' => ['required', 'string', 'max:50'],
            'score' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'notes' => ['nullable', 'string'],
            'next_goal' => ['nullable', 'string'],
        ]);

        if ($error !== null) {
            return $error;
        }

        $generus = Generus::where('registration_number', trim($data['generus_registration_number']))->first();

        if ($generus === null) {
            return 'Generus dengan nomor registrasi tersebut tidak ditemukan.';
        }

        $allowedIds = StudentScope::ids($this->user, 'manage-progress-tracking');

        if ($allowedIds !== null && ! in_array($generus->id, $allowedIds, true)) {
            return 'Generus berada di luar wilayah akses Anda.';
        }

        $academicYear = AcademicYear::where('code', trim($data['academic_year_code']))->where('is_active', true)->first();
        $semester = $academicYear === null ? null : Semester::where('academic_year_id', $academicYear->id)
            ->where('code', trim($data['semester_code']))->where('is_active', true)->first();

        if ($academicYear === null || $semester === null) {
            return 'Kode tahun ajaran atau semester tidak ditemukan atau tidak aktif.';
        }

        ProgressTrack::updateOrCreate(
            [
                'generus_id' => $generus->id,
                'academic_year_id' => $academicYear->id,
                'semester_id' => $semester->id,
                'period_label' => trim($data['period_label']),
            ],
            [
                'overall_status' => trim($data['overall_status']),
                'score' => $data['score'] ?? null,
                'notes' => $data['notes'] ?? null,
                'next_goal' => $data['next_goal'] ?? null,
            ],
        );

        return null;
    }
}
