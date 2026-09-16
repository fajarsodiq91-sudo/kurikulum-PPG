<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ActivitySchedule extends Model
{
    protected $table = 'activity_schedules';

    protected $fillable = [
        'organization_unit_id',
        'title',
        'type',
        'scheduled_at',
        'end_at',
        'location',
        'status',
        'notes',
    ];

    public function organizationUnit(): BelongsTo
    {
        return $this->belongsTo(OrganizationUnit::class);
    }
}
