<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Communication extends Model
{
    protected $table = 'communications';

    protected $fillable = [
        'training_id',
        'channel',
        'subject',
        'message',
        'status',
    ];

    public function training(): BelongsTo
    {
        return $this->belongsTo(Training::class);
    }
}
