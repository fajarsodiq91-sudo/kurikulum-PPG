<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Teacher extends Model
{
    protected $fillable = [
        'name',
        'gender',
        'phone',
        'email',
        'status',
        'notes',
    ];

    public function learningSessions(): HasMany
    {
        return $this->hasMany(LearningSession::class);
    }

    public function evaluations(): HasMany
    {
        return $this->hasMany(Evaluation::class);
    }

    public function followUps(): HasMany
    {
        return $this->hasMany(FollowUp::class);
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(Assignment::class);
    }

    /**
     * Records referencing this teacher block deletion (foreign keys restrict on delete).
     */
    public function hasActivityHistory(): bool
    {
        return $this->learningSessions()->exists()
            || $this->evaluations()->exists()
            || $this->followUps()->exists()
            || $this->assignments()->exists();
    }
}
