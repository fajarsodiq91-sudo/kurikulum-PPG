<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * One kelompok's part of a semester-2 rapor publication: which of its generus the Pelaksana
 * PPG Kelompok flagged as "tinggal kelas" (via retainedGenerus — everyone else is "naik" by
 * default). Confirming it promotes the "naik" generus and marks this done; once every
 * confirmation under the same ReportCardPublication is done, that publication publishes.
 */
class ReportCardGroupConfirmation extends Model
{
    public const CONFIRM_PERMISSION = 'confirm-report-card-publishing';

    public const STATUS_PENDING = 'pending';

    public const STATUS_CONFIRMED = 'confirmed';

    protected $fillable = [
        'report_card_publication_id',
        'group_id',
        'status',
        'confirmed_by_id',
        'confirmed_at',
    ];

    protected $casts = [
        'confirmed_at' => 'datetime',
    ];

    public function publication(): BelongsTo
    {
        return $this->belongsTo(ReportCardPublication::class, 'report_card_publication_id');
    }

    public function group(): BelongsTo
    {
        return $this->belongsTo(Group::class);
    }

    public function confirmedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'confirmed_by_id');
    }

    public function retainedGenerus(): BelongsToMany
    {
        return $this->belongsToMany(Generus::class, 'report_card_retained_generus')->withTimestamps();
    }

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }
}
