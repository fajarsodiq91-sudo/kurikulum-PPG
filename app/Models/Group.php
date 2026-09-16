<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Group extends Model
{
    protected $fillable = [
        'village_id',
        'name',
        'code',
        'slug',
        'is_active',
        'description',
    ];

    public function village(): BelongsTo
    {
        return $this->belongsTo(Village::class);
    }
}
