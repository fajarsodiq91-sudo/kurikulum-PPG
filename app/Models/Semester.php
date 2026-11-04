<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

class Semester extends Model
{
    protected $fillable = [
        'academic_year_id',
        'name',
        'code',
        'sort_order',
        'start_date',
        'end_date',
        'is_active',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
    ];

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }

    /**
     * Whether $date falls within this semester's configured period.
     */
    public function containsDate(Carbon $date): bool
    {
        return $this->start_date !== null
            && $this->end_date !== null
            && $date->betweenIncluded($this->start_date, $this->end_date);
    }
}
