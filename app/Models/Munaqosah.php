<?php

namespace App\Models;

use App\Support\ReportCardScores;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Munaqosah extends Model
{
    public const MANAGE_PERMISSION = 'manage-munaqosah';

    protected $table = 'munaqosahs';

    protected $fillable = [
        'generus_id',
        'academic_year_id',
        'semester_id',
        'title',
        'type',
        'score',
        'result',
        'grade_scale_id',
        'grade_description',
        'status',
        'notes',
    ];

    protected static function booted(): void
    {
        static::saved(fn (self $munaqosah) => ReportCardScores::recalculate($munaqosah->generus_id, $munaqosah->academic_year_id, $munaqosah->semester_id));
        static::deleted(fn (self $munaqosah) => ReportCardScores::recalculate($munaqosah->generus_id, $munaqosah->academic_year_id, $munaqosah->semester_id));
    }

    public function generus(): BelongsTo
    {
        return $this->belongsTo(Generus::class);
    }

    public function gradeScale(): BelongsTo
    {
        return $this->belongsTo(GradeScale::class);
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
