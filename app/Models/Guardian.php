<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Guardian extends Model
{
    protected $fillable = [
        'full_name',
        'relationship',
        'phone',
        'address',
        'status',
        'notes',
    ];

    public function generus(): BelongsToMany
    {
        return $this->belongsToMany(Generus::class, 'generus_guardian')->withTimestamps();
    }
}
