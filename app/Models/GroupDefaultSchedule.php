<?php

namespace App\Models;

use App\Models\Concerns\HasRecurringSchedulePattern;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A kelompok's own recurring KBM pattern for one jenjang — the group-scoped counterpart to
 * DefaultSchedule (which is set Daerah-wide). Visible on the dashboard calendar to Daerah,
 * the group's own desa, and the group itself.
 */
class GroupDefaultSchedule extends Model
{
    use HasRecurringSchedulePattern;

    protected $fillable = [
        'group_id',
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

    public function group(): BelongsTo
    {
        return $this->belongsTo(Group::class);
    }

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
        return $this->hasMany(GroupDefaultScheduleException::class);
    }
}
