<?php

namespace App\Support\Sheets;

use App\Models\AcademicYear;
use App\Models\AnnualAudit;
use App\Models\Generus;
use App\Models\Semester;
use App\Models\User;
use App\Support\StudentScope;

class AnnualAuditSheet extends Sheet
{
    public function __construct(private ?User $user = null) {}

    public function filename(): string
    {
        return 'audit-tahunan.xlsx';
    }

    public function headings(): array
    {
        return ['generus_registration_number', 'academic_year_code', 'semester_code', 'audit_type', 'overall_status', 'summary', 'recommendation'];
    }

    public function rows(): iterable
    {
        $ids = StudentScope::ids($this->user, 'manage-annual-audit');

        foreach (StudentScope::limit(AnnualAudit::with(['generus', 'academicYear', 'semester']), $ids, 'generus_id')->orderBy('id')->cursor() as $audit) {
            yield [
                $audit->generus?->registration_number,
                $audit->academicYear?->code,
                $audit->semester?->code,
                $audit->audit_type,
                $audit->overall_status,
                $audit->summary,
                $audit->recommendation,
            ];
        }
    }

    public function importRow(array $row): ?string
    {
        [$data, $error] = $this->validated($row, [
            'generus_registration_number' => ['required', 'string', 'max:50'],
            'academic_year_code' => ['required', 'string', 'max:50'],
            'semester_code' => ['required', 'string', 'max:50'],
            'audit_type' => ['required', 'string', 'max:50'],
            'overall_status' => ['required', 'string', 'max:50'],
            'summary' => ['nullable', 'string'],
            'recommendation' => ['nullable', 'string'],
        ]);

        if ($error !== null) {
            return $error;
        }

        $generus = Generus::where('registration_number', trim($data['generus_registration_number']))->first();

        if ($generus === null) {
            return 'Generus dengan nomor registrasi tersebut tidak ditemukan.';
        }

        $allowedIds = StudentScope::ids($this->user, 'manage-annual-audit');

        if ($allowedIds !== null && ! in_array($generus->id, $allowedIds, true)) {
            return 'Generus berada di luar wilayah akses Anda.';
        }

        $academicYear = AcademicYear::where('code', trim($data['academic_year_code']))->where('is_active', true)->first();
        $semester = $academicYear === null ? null : Semester::where('academic_year_id', $academicYear->id)
            ->where('code', trim($data['semester_code']))->where('is_active', true)->first();

        if ($academicYear === null || $semester === null) {
            return 'Kode tahun ajaran atau semester tidak ditemukan atau tidak aktif.';
        }

        AnnualAudit::updateOrCreate(
            [
                'generus_id' => $generus->id,
                'academic_year_id' => $academicYear->id,
                'semester_id' => $semester->id,
                'audit_type' => trim($data['audit_type']),
            ],
            [
                'overall_status' => trim($data['overall_status']),
                'summary' => $data['summary'] ?? null,
                'recommendation' => $data['recommendation'] ?? null,
            ],
        );

        return null;
    }
}
