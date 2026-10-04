<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MaterialCategory extends Model
{
    protected $fillable = [
        'class_grade_id',
        'semester_id',
        'name',
        'code',
        'sort_order',
        'is_active',
        'description',
    ];

    public function classGrade(): BelongsTo
    {
        return $this->belongsTo(ClassGrade::class);
    }

    public function semester(): BelongsTo
    {
        return $this->belongsTo(Semester::class);
    }

    public function materialChapters(): HasMany
    {
        return $this->hasMany(MaterialChapter::class);
    }
}
