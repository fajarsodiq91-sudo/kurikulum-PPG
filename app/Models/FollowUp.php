<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FollowUp extends Model
{
    protected $table = 'follow_ups';

    protected $fillable = [
        'generus_id',
        'teacher_id',
        'title',
        'priority',
        'follow_up_date',
        'status',
        'notes',
        'next_action',
    ];

    public function generus(): BelongsTo
    {
        return $this->belongsTo(Generus::class);
    }

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(Teacher::class);
    }
}
