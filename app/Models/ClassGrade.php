<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ClassGrade extends Model
{
    protected $fillable = [
        'level_id',
        'name',
        'code',
        'sort_order',
        'is_active',
        'description',
    ];

    public function level(): BelongsTo
    {
        return $this->belongsTo(Level::class);
    }
}
