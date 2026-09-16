<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LearningMaterial extends Model
{
    protected $table = 'learning_materials';

    protected $fillable = [
        'curriculum_program_id',
        'title',
        'code',
        'level_id',
        'semester_id',
        'academic_year_id',
        'description',
        'is_active',
    ];

    public function curriculumProgram(): BelongsTo
    {
        return $this->belongsTo(CurriculumProgram::class);
    }

    public function level(): BelongsTo
    {
        return $this->belongsTo(Level::class);
    }

    public function semester(): BelongsTo
    {
        return $this->belongsTo(Semester::class);
    }

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }
}
