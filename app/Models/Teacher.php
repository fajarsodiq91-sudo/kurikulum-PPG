<?php

namespace App\Models;

use App\Support\MemberAccounts;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
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

    /**
     * Teacher numbers are YYMM, the fixed code 99 and a 3-digit monthly sequence, which keeps
     * them distinct from generus numbers (YYMM plus a 4-digit sequence).
     */
    public static function nextRegistrationNumber(): string
    {
        $prefix = now()->format('ym').'99';
        $lastNumber = self::where('registration_number', 'like', $prefix.'%')
            ->orderByDesc('registration_number')
            ->value('registration_number');

        $sequence = $lastNumber !== null && preg_match('/^'.preg_quote($prefix, '/').'(\d{3})$/', $lastNumber, $matches)
            ? (int) $matches[1] + 1
            : 1;

        return $prefix.str_pad((string) $sequence, 3, '0', STR_PAD_LEFT);
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

    public function students(): BelongsToMany
    {
        return $this->belongsToMany(Generus::class, 'teacher_generus')->withTimestamps();
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
