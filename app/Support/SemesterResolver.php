<?php

namespace App\Support;

use App\Models\Semester;
use Illuminate\Support\Carbon;

class SemesterResolver
{
    /**
     * The semester whose configured date range covers $date, preferring the currently active one.
     */
    public static function resolveFor(Carbon $date): ?Semester
    {
        return Semester::with('academicYear')
            ->whereNotNull('start_date')
            ->whereNotNull('end_date')
            ->whereDate('start_date', '<=', $date)
            ->whereDate('end_date', '>=', $date)
            ->orderByDesc('is_active')
            ->first();
    }
}
