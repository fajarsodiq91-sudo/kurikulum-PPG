<?php

namespace App\Models;

use App\Support\MemberAccounts;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
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
        'region_id',
        'village_id',
        'group_id',
        'photo',
        'status',
        'notes',
    ];

    protected static function booted(): void
    {
        static::created(fn (self $teacher) => MemberAccounts::forTeacher($teacher));
        static::updated(function (self $teacher): void {
            if ($teacher->wasChanged('name')) {
                MemberAccounts::rename('teacher_id', $teacher->id, $teacher->name);
            }

            if ($teacher->wasChanged('status')) {
                MemberAccounts::setStatus('teacher_id', $teacher->id, $teacher->status === 'active' ? 'active' : 'inactive');
            }
        });
        static::deleting(fn (self $teacher) => MemberAccounts::setStatus('teacher_id', $teacher->id, 'inactive'));
    }

    public function region(): BelongsTo
    {
        return $this->belongsTo(Region::class);
    }

    public function village(): BelongsTo
    {
        return $this->belongsTo(Village::class);
    }

    public function group(): BelongsTo
    {
        return $this->belongsTo(Group::class);
    }

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
