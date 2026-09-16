<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EvaluationScore extends Model
{
    protected $table = 'evaluation_scores';

    protected $fillable = [
        'evaluation_id',
        'generus_id',
        'score',
        'grade',
        'notes',
    ];

    public function evaluation(): BelongsTo
    {
        return $this->belongsTo(Evaluation::class);
    }

    public function generus(): BelongsTo
    {
        return $this->belongsTo(Generus::class);
    }
}
