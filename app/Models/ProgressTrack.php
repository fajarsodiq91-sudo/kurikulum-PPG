<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProgressTrack extends Model
{
    protected $table = 'progress_tracks';

    protected $fillable = [
        'generus_id',
        'academic_year_id',
        'semester_id',
        'period_label',
        'overall_status',
        'score',
        'notes',
        'next_goal',
    ];

    public function generus(): BelongsTo
    {
        return $this->belongsTo(Generus::class);
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
