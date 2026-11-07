<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MusyawarohAttendee extends Model
{
    public const STATUS_HADIR = 'hadir';

    public const STATUS_IZIN = 'izin';

    public const STATUS_ALPHA = 'alpha';

    /**
     * @var list<string>
     */
    public const STATUSES = [self::STATUS_HADIR, self::STATUS_IZIN, self::STATUS_ALPHA];

    protected $fillable = [
        'musyawaroh_id',
        'user_id',
        'manual_name',
        'status',
    ];

    public function musyawaroh(): BelongsTo
    {
        return $this->belongsTo(Musyawaroh::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function displayName(): string
    {
        return $this->user?->name ?? (string) $this->manual_name;
    }

    public function statusLabel(): string
    {
        return match ($this->status) {
            self::STATUS_IZIN => 'Izin',
            self::STATUS_ALPHA => 'Alpha',
            default => 'Hadir',
        };
    }
}
