<?php

namespace App\Models;

use App\Models\Concerns\HasRecurringSchedulePattern;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DefaultSchedule extends Model
{
    use HasRecurringSchedulePattern;

    protected $fillable = [
        'level_id',
        'academic_year_id',
        'semester_id',
        'days_of_week',
        'start_date',
        'end_date',
        'start_time',
        'end_time',
        'categories',
    ];

    protected $casts = [
        'days_of_week' => 'array',
        'categories' => 'array',
        'start_date' => 'date',
        'end_date' => 'date',
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

    public function exceptions(): HasMany
    {
        return $this->hasMany(DefaultScheduleException::class);
    }
}
