<?php

namespace App\Support\Sheets;

use App\Models\ReportCard;
use App\Models\User;
use App\Support\StudentScope;

/**
 * Export only: final_score is computed automatically from Munaqosah and Ujian averages
 * (see ReportCardScores::recalculate), so re-importing it would fight that calculation.
 */
class ReportCardSheet extends Sheet
{
    public function __construct(private ?User $user = null) {}

    public function filename(): string
    {
        return 'rapor.xlsx';
    }

    public function headings(): array
    {
        return ['generus_registration_number', 'academic_year_code', 'semester_code', 'final_score', 'predicate', 'recommendation', 'remarks'];
    }

    public function rows(): iterable
    {
        $ids = StudentScope::ids($this->user, 'manage-report-cards');

        foreach (StudentScope::limit(ReportCard::with(['generus', 'academicYear', 'semester']), $ids, 'generus_id')->orderBy('id')->cursor() as $reportCard) {
            yield [
                $reportCard->generus?->registration_number,
                $reportCard->academicYear?->code,
                $reportCard->semester?->code,
                $reportCard->final_score,
                $reportCard->predicate,
                $reportCard->recommendation,
                $reportCard->remarks,
            ];
        }
    }

    public function importRow(array $row): ?string
    {
        return 'Import tidak didukung untuk Rapor karena nilai akhir dihitung otomatis oleh sistem.';
    }
}
