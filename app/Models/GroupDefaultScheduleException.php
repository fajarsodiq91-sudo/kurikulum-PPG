<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GroupDefaultScheduleException extends Model
{
    protected $fillable = [
        'group_default_schedule_id',
        'exception_date',
        'reason',
    ];

    protected $casts = [
        'exception_date' => 'date',
    ];

    public function groupDefaultSchedule(): BelongsTo
    {
        return $this->belongsTo(GroupDefaultSchedule::class);
    }
}
