<?php

namespace App\Support\Sheets;

use App\Models\AcademicYear;
use App\Models\Generus;
use App\Models\GradeScale;
use App\Models\Semester;
use App\Models\Ujian;
use App\Models\User;
use App\Support\StudentScope;

class UjianSheet extends Sheet
{
    public function __construct(private ?User $user = null) {}

    public function filename(): string
    {
        return 'ujian.xlsx';
    }

    public function headings(): array
    {
        return ['generus_registration_number', 'academic_year_code', 'semester_code', 'title', 'type', 'score', 'result', 'status', 'notes'];
    }

    public function rows(): iterable
    {
        $ids = StudentScope::viewIds($this->user, Ujian::MANAGE_PERMISSION);

        foreach (StudentScope::limit(Ujian::with(['generus', 'academicYear', 'semester']), $ids, 'generus_id')->orderBy('id')->cursor() as $ujian) {
            yield [
                $ujian->generus?->registration_number,
                $ujian->academicYear?->code,
                $ujian->semester?->code,
                $ujian->title,
                $ujian->type,
                $ujian->score,
                $ujian->result,
                $ujian->status,
                $ujian->notes,
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
            'type' => ['required', 'string', 'max:50'],
            'score' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'result' => ['nullable', 'string', 'max:50'],
            'status' => ['required', 'string', 'max:50'],
            'notes' => ['nullable', 'string'],
        ]);

        if ($error !== null) {
            return $error;
        }

        $generus = Generus::where('registration_number', trim($data['generus_registration_number']))->first();

        if ($generus === null) {
            return 'Generus dengan nomor registrasi tersebut tidak ditemukan.';
        }

        $allowedIds = StudentScope::manageIds($this->user, Ujian::MANAGE_PERMISSION);

        if ($allowedIds !== null && ! in_array($generus->id, $allowedIds, true)) {
            return 'Generus berada di luar wilayah akses Anda.';
        }

        $academicYear = AcademicYear::where('code', trim($data['academic_year_code']))->where('is_active', true)->first();
        $semester = $academicYear === null ? null : Semester::where('academic_year_id', $academicYear->id)
            ->where('code', trim($data['semester_code']))->where('is_active', true)->first();

        if ($academicYear === null || $semester === null) {
            return 'Kode tahun ajaran atau semester tidak ditemukan atau tidak aktif.';
        }

        $result = filled($data['result'] ?? null) ? trim($data['result']) : null;
        $gradeScaleId = null;
        $gradeDescription = null;

        if ($result === null && filled($data['score'] ?? null)) {
            $scale = GradeScale::resolve((float) $data['score']);

            if ($scale) {
                $result = $scale->grade;
                $gradeScaleId = $scale->id;
                $gradeDescription = $scale->description;
            }
        }

        Ujian::updateOrCreate(
            [
                'generus_id' => $generus->id,
                'academic_year_id' => $academicYear->id,
                'semester_id' => $semester->id,
                'type' => trim($data['type']),
                'title' => trim($data['title']),
            ],
            [
                'score' => $data['score'] ?? null,
                'result' => $result,
                'grade_scale_id' => $gradeScaleId,
                'grade_description' => $gradeDescription,
                'status' => trim($data['status']),
                'notes' => $data['notes'] ?? null,
            ],
        );

        return null;
    }
}
