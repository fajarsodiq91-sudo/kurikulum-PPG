<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MusyawarohAttendee extends Model
{
    protected $fillable = [
        'musyawaroh_id',
        'user_id',
        'manual_name',
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
}
