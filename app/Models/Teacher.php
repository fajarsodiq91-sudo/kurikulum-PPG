<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

class Teacher extends Model
{
    public const PHOTO_DIRECTORY = 'teacher-photos';

    protected $fillable = [
        'registration_number',
        'name',
        'gender',
        'phone',
        'email',
        'photo',
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

    /**
     * Photos live on the private disk, so pages embed them inline instead of linking to a public URL.
     */
    public function photoDataUri(): ?string
    {
        if (blank($this->photo) || ! Storage::exists($this->photo)) {
            return null;
        }

        return 'data:'.Storage::mimeType($this->photo).';base64,'.base64_encode(Storage::get($this->photo));
    }
}
