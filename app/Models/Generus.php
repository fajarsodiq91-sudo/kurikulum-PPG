<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Generus extends Model
{
    protected $table = 'generus';

    protected $fillable = [
        'registration_number',
        'record_number',
        'full_name',
        'school_name',
        'nis',
        'father_name',
        'mother_name',
        'father_occupation',
        'mother_occupation',
        'phone_number',
        'gender',
        'birth_place',
        'birth_date',
        'birth_order',
        'sibling_count',
        'school_grade',
        'learning_class',
        'educational_level',
        'photo',
        'status',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'birth_date' => 'date',
            'birth_order' => 'integer',
            'sibling_count' => 'integer',
        ];
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(GenerusAssignment::class);
    }
}
