<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * PPG Daerah's confirmation to publish rapor for one academic year + semester. At semester 2
 * (requires_group_confirmation=true) this stays pending_group_confirmation until every
 * ReportCardGroupConfirmation for it is confirmed — each kelompok gets one last chance to
 * flag who's actually "tinggal kelas" before kenaikan kelas lands and rapor go out. At
 * semester 1 there's no kenaikan kelas, so it publishes immediately.
 */
class ReportCardPublication extends Model
{
    public const MANAGE_PERMISSION = 'manage-report-card-publishing';

    public const STATUS_PENDING_GROUP_CONFIRMATION = 'pending_group_confirmation';

    public const STATUS_PUBLISHED = 'published';

    protected $fillable = [
        'academic_year_id',
        'semester_id',
        'requires_group_confirmation',
        'status',
        'initiated_by_id',
        'published_at',
    ];

    protected $casts = [
        'requires_group_confirmation' => 'boolean',
        'published_at' => 'datetime',
    ];

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }

    public function semester(): BelongsTo
    {
        return $this->belongsTo(Semester::class);
    }

    public function initiatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'initiated_by_id');
    }

    public function groupConfirmations(): HasMany
    {
        return $this->hasMany(ReportCardGroupConfirmation::class);
    }

    public function isPublished(): bool
    {
        return $this->status === self::STATUS_PUBLISHED;
    }

    /**
     * Whether every kelompok required to confirm this publication already has.
     */
    public function allGroupsConfirmed(): bool
    {
        return $this->groupConfirmations()->where('status', '!=', ReportCardGroupConfirmation::STATUS_CONFIRMED)->doesntExist();
    }
}
