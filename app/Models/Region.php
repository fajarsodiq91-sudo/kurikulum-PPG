<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Region extends Model
{
    protected $fillable = [
        'name',
        'code',
        'slug',
        'is_active',
        'description',
    ];

    public function villages(): HasMany
    {
        return $this->hasMany(Village::class);
    }
}
