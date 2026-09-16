<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LearningSession extends Model
{
    protected $table = 'learning_sessions';

    protected $fillable = [
        'teacher_id',
        'material_id',
        'session_date',
        'start_time',
        'end_time',
        'location',
        'status',
        'notes',
    ];

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(Teacher::class);
    }

    public function material(): BelongsTo
    {
        return $this->belongsTo(LearningMaterial::class, 'material_id');
    }

    public function attendances(): HasMany
    {
        return $this->hasMany(SessionAttendance::class);
    }
}
