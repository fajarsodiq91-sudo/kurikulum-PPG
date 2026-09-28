<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SessionAttendance extends Model
{
    public const METHOD_MANUAL = 'manual';

    public const METHOD_QR = 'qr';

    public const METHOD_RFID = 'rfid';

    public const METHOD_FACE = 'face';

    protected $table = 'session_attendances';

    protected $fillable = [
        'learning_session_id',
        'generus_id',
        'status',
        'method',
        'notes',
    ];

    public function learningSession(): BelongsTo
    {
        return $this->belongsTo(LearningSession::class);
    }

    public function generus(): BelongsTo
    {
        return $this->belongsTo(Generus::class);
    }
}
