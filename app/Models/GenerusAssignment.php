<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GenerusAssignment extends Model
{
    protected $table = 'generus_assignments';

    protected $fillable = [
        'generus_id',
        'region_id',
        'village_id',
        'group_id',
        'level_id',
        'academic_year_id',
        'status',
        'assigned_at',
        'ended_at',
        'notes',
    ];

    public function generus(): BelongsTo
    {
        return $this->belongsTo(Generus::class);
    }

    public function region(): BelongsTo
    {
        return $this->belongsTo(Region::class);
    }

    public function village(): BelongsTo
    {
        return $this->belongsTo(Village::class);
    }

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
}
