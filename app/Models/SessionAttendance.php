<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SessionAttendance extends Model
{
    protected $table = 'session_attendances';

    protected $fillable = [
        'learning_session_id',
        'generus_id',
        'status',
        'notes',
    ];

    public function learningSession(): BelongsTo
    {
        return $this->belongsTo(LearningSession::class);
    }

    public function generus(): BelongsTo
    {
        return $this->belongsTo(Generus::class);
    }
}
