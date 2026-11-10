<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A kelas-KBM's munaqosah window for one semester, opened by PPG Daerah: when it runs
 * (scheduled_start/end) and what materi is being tested. Pelaksana Kelompok may only record
 * munaqosah type=semester-final scores for a class/semester while its schedule is_open.
 */
class MunaqosahSchedule extends Model
{
    public const MANAGE_PERMISSION = 'manage-munaqosah-schedule';

    protected $fillable = [
        'class_grade_id',
        'academic_year_id',
        'semester_id',
        'type',
        'materials',
        'scheduled_start',
        'scheduled_end',
        'is_open',
        'opened_by_id',
    ];

    protected $casts = [
        'scheduled_start' => 'date',
        'scheduled_end' => 'date',
        'is_open' => 'boolean',
    ];

    public function classGrade(): BelongsTo
    {
        return $this->belongsTo(ClassGrade::class);
    }

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }

    public function semester(): BelongsTo
    {
        return $this->belongsTo(Semester::class);
    }

    public function openedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'opened_by_id');
    }
}
