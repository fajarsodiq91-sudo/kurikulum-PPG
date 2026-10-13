<?php

namespace App\Support\Sheets;

use App\Models\AcademicYear;
use App\Models\Generus;
use App\Models\Milestone;
use App\Models\Semester;
use App\Models\User;
use App\Support\StudentScope;

class MilestoneSheet extends Sheet
{
    public function __construct(private ?User $user = null) {}

    public function filename(): string
    {
        return 'milestone.xlsx';
    }

    public function headings(): array
    {
        return ['generus_registration_number', 'academic_year_code', 'semester_code', 'title', 'description', 'achievement_date', 'status', 'score'];
    }

    public function rows(): iterable
    {
        $ids = StudentScope::ids($this->user, 'manage-milestones');

        foreach (StudentScope::limit(Milestone::with(['generus', 'academicYear', 'semester']), $ids, 'generus_id')->orderBy('id')->cursor() as $milestone) {
            yield [
                $milestone->generus?->registration_number,
                $milestone->academicYear?->code,
                $milestone->semester?->code,
                $milestone->title,
                $milestone->description,
                $milestone->achievement_date,
                $milestone->status,
                $milestone->score,
            ];
        }
    }

    public function importRow(array $row): ?string
    {
        [$data, $error] = $this->validated($row, [
            'generus_registration_number' => ['required', 'string', 'max:50'],
            'academic_year_code' => ['required', 'string', 'max:50'],
            'semester_code' => ['required', 'string', 'max:50'],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'achievement_date' => ['required', 'date'],
            'status' => ['required', 'string', 'max:50'],
            'score' => ['nullable', 'numeric', 'min:0', 'max:100'],
        ]);

        if ($error !== null) {
            return $error;
        }

        $generus = Generus::where('registration_number', trim($data['generus_registration_number']))->first();

        if ($generus === null) {
            return 'Generus dengan nomor registrasi tersebut tidak ditemukan.';
        }

        $allowedIds = StudentScope::ids($this->user, 'manage-milestones');

        if ($allowedIds !== null && ! in_array($generus->id, $allowedIds, true)) {
            return 'Generus berada di luar wilayah akses Anda.';
        }

        $academicYear = AcademicYear::where('code', trim($data['academic_year_code']))->where('is_active', true)->first();
        $semester = $academicYear === null ? null : Semester::where('academic_year_id', $academicYear->id)
            ->where('code', trim($data['semester_code']))->where('is_active', true)->first();

        if ($academicYear === null || $semester === null) {
            return 'Kode tahun ajaran atau semester tidak ditemukan atau tidak aktif.';
        }

        Milestone::updateOrCreate(
            [
                'generus_id' => $generus->id,
                'academic_year_id' => $academicYear->id,
                'semester_id' => $semester->id,
                'title' => trim($data['title']),
                'achievement_date' => $data['achievement_date'],
            ],
            [
                'description' => $data['description'] ?? null,
                'status' => trim($data['status']),
                'score' => $data['score'] ?? null,
            ],
        );

        return null;
    }
}
