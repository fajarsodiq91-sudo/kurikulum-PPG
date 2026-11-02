<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

class DefaultSchedule extends Model
{
    protected $fillable = [
        'level_id',
        'academic_year_id',
        'semester_id',
        'days_of_week',
        'start_date',
        'end_date',
        'start_time',
        'end_time',
    ];

    protected $casts = [
        'days_of_week' => 'array',
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

    /**
     * Whether this pattern lands on $date: within its date range, on one of its weekdays,
     * and not marked as a holiday.
     */
    public function occursOn(Carbon $date): bool
    {
        if ($date->lt($this->start_date) || $date->gt($this->end_date)) {
            return false;
        }

        if (! in_array($date->isoWeekday(), $this->days_of_week ?? [], true)) {
            return false;
        }

        return ! $this->exceptions->contains(fn (DefaultScheduleException $exception): bool => $exception->exception_date->isSameDay($date));
    }
}
