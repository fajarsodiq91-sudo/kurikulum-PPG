<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ParentCommunication extends Model
{
    public const CHANNELS = ['WhatsApp', 'Telepon', 'Tatap muka', 'Surat', 'Lainnya'];

    protected $fillable = [
        'generus_id',
        'guardian_id',
        'teacher_id',
        'channel',
        'communicated_at',
        'subject',
        'message',
    ];

    protected function casts(): array
    {
        return ['communicated_at' => 'date'];
    }

    public function generus(): BelongsTo
    {
        return $this->belongsTo(Generus::class);
    }

    public function guardian(): BelongsTo
    {
        return $this->belongsTo(Guardian::class);
    }

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(Teacher::class);
    }
}
