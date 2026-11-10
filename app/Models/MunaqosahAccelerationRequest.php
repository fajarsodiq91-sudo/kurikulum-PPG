<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Pelaksana PPG Kelompok's proposal to let one generus sit an out-of-cycle ("akselerasi")
 * munaqosah, bypassing the semester-2-only promotion path. Perwakilan PPG Desa or PPG
 * Daerah approves or rejects it; once approved, that generus may be scored on a
 * type=akselerasi munaqosah which can promote them regardless of semester.
 */
class MunaqosahAccelerationRequest extends Model
{
    public const PROPOSE_PERMISSION = 'propose-munaqosah-acceleration';

    public const APPROVE_PERMISSION = 'approve-munaqosah-acceleration';

    public const STATUS_PENDING = 'pending';

    public const STATUS_APPROVED = 'approved';

    public const STATUS_REJECTED = 'rejected';

    protected $fillable = [
        'generus_id',
        'class_grade_id',
        'requested_by_id',
        'reason',
        'status',
        'decided_by_id',
        'decided_at',
        'decision_notes',
    ];

    protected $casts = [
        'decided_at' => 'datetime',
    ];

    public function generus(): BelongsTo
    {
        return $this->belongsTo(Generus::class);
    }

    public function classGrade(): BelongsTo
    {
        return $this->belongsTo(ClassGrade::class);
    }

    public function requestedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by_id');
    }

    public function decidedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'decided_by_id');
    }

    public function munaqosahs(): HasMany
    {
        return $this->hasMany(Munaqosah::class);
    }

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function isApproved(): bool
    {
        return $this->status === self::STATUS_APPROVED;
    }
}
