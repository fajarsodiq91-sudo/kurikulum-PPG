<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DefaultSchedule extends Model
{
    protected $fillable = [
        'level_id',
        'academic_year_id',
        'semester_id',
        'week_number',
        'scheduled_date',
        'day_of_week',
        'start_time',
        'end_time',
    ];

    public function level(): BelongsTo
    {
        return $this->belongsTo(Level::class);
    }

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }

    public function semester(): BelongsTo
    {
        return $this->belongsTo(Semester::class);
    }
}
