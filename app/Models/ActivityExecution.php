<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ActivityExecution extends Model
{
    protected $table = 'activity_executions';

    protected $fillable = [
        'activity_schedule_id',
        'actual_date',
        'status',
        'attendance_count',
        'outcome',
        'notes',
    ];

    public function activitySchedule(): BelongsTo
    {
        return $this->belongsTo(ActivitySchedule::class);
    }
}
