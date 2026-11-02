<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DefaultScheduleException extends Model
{
    protected $fillable = [
        'default_schedule_id',
        'exception_date',
        'reason',
    ];

    protected $casts = [
        'exception_date' => 'date',
    ];

    public function defaultSchedule(): BelongsTo
    {
        return $this->belongsTo(DefaultSchedule::class);
    }
}
