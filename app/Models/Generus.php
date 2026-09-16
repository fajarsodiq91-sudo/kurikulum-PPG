<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Generus extends Model
{
    protected $table = 'generus';

    protected $fillable = [
        'registration_number',
        'full_name',
        'gender',
        'birth_date',
        'photo',
        'status',
        'notes',
    ];

    public function assignments(): HasMany
    {
        return $this->hasMany(GenerusAssignment::class);
    }
}
