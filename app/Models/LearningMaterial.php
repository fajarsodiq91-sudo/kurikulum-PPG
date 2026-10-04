<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LearningMaterial extends Model
{
    protected $table = 'learning_materials';

    protected $fillable = [
        'material_chapter_id',
        'title',
        'code',
        'sort_order',
        'academic_year_id',
        'description',
        'is_active',
    ];

    public function materialChapter(): BelongsTo
    {
        return $this->belongsTo(MaterialChapter::class);
    }

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }
}
