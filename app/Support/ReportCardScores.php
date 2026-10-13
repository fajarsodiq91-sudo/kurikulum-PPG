<?php

namespace App\Support;

use App\Models\Munaqosah;
use App\Models\ReportCard;
use App\Models\Ujian;

/**
 * Keeps a generus' report card final score in sync with their munaqosah and ujian (ulangan
 * harian/mingguan/bulanan) scores within the same semester: the final score is the average of
 * the two, or the munaqosah score alone when no ujian score exists yet.
 */
class ReportCardScores
{
    public static function recalculate(int $generusId, int $academicYearId, int $semesterId): void
    {
        $munaqosahAverage = Munaqosah::query()
            ->where('generus_id', $generusId)
            ->where('academic_year_id', $academicYearId)
            ->where('semester_id', $semesterId)
            ->whereNotNull('score')
            ->avg('score');

        $ujianAverage = Ujian::query()
            ->where('generus_id', $generusId)
            ->where('academic_year_id', $academicYearId)
            ->where('semester_id', $semesterId)
            ->whereNotNull('score')
            ->avg('score');

        $finalScore = match (true) {
            $munaqosahAverage === null && $ujianAverage === null => null,
            $ujianAverage === null => round((float) $munaqosahAverage, 2),
            $munaqosahAverage === null => round((float) $ujianAverage, 2),
            default => round(((float) $munaqosahAverage + (float) $ujianAverage) / 2, 2),
        };

        ReportCard::updateOrCreate(
            ['generus_id' => $generusId, 'academic_year_id' => $academicYearId, 'semester_id' => $semesterId],
            ['final_score' => $finalScore],
        );
    }
}
