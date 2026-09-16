<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AnnualAudit extends Model
{
    protected $table = 'annual_audits';

    protected $fillable = [
        'generus_id',
        'academic_year_id',
        'semester_id',
        'audit_type',
        'overall_status',
        'summary',
        'recommendation',
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
