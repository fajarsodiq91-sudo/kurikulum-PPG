<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Level extends Model
{
    protected $fillable = [
        'name',
        'code',
        'sort_order',
        'is_active',
        'description',
    ];

    public function classGrades(): HasMany
    {
        return $this->hasMany(ClassGrade::class);
    }
}
