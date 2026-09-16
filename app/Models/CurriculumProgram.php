<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CurriculumProgram extends Model
{
    protected $table = 'curriculum_programs';

    protected $fillable = [
        'name',
        'code',
        'description',
        'is_active',
    ];

    public function learningMaterials(): HasMany
    {
        return $this->hasMany(LearningMaterial::class);
    }
}
