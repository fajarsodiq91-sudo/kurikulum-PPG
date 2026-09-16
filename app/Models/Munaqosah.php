<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Munaqosah extends Model
{
    protected $table = 'munaqosahs';

    protected $fillable = [
        'generus_id',
        'academic_year_id',
        'semester_id',
        'title',
        'type',
        'score',
        'result',
        'status',
        'notes',
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
