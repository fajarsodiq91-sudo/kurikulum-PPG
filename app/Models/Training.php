<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Training extends Model
{
    protected $table = 'trainings';

    protected $fillable = [
        'title',
        'type',
        'academic_year_id',
        'scheduled_at',
        'location',
        'status',
        'notes',
    ];

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }

    public function communications(): HasMany
    {
        return $this->hasMany(Communication::class);
    }
}
