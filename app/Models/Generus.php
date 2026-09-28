<?php

namespace App\Models;

use App\Support\MemberAccounts;
use App\Support\ParentGuardians;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

class Generus extends Model
{
    use SoftDeletes;

    public const MANAGE_PERMISSION = 'manage-generus';

    public const PHOTO_DIRECTORY = 'generus-photos';

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
        'school_grade_id',
        'learning_class_id',
        'photo',
        'rfid_uid',
        'status',
        'transfer_destination',
        'notes',
    ];

    protected static function booted(): void
    {
        static::created(fn (self $generus) => MemberAccounts::forGenerus($generus));
        static::saved(fn (self $generus) => ParentGuardians::sync($generus));
        static::updated(function (self $generus): void {
            if ($generus->wasChanged('full_name')) {
                MemberAccounts::rename('generus_id', $generus->id, $generus->full_name);
            }
        });
        static::deleted(fn (self $generus) => MemberAccounts::setStatus('generus_id', $generus->id, 'inactive'));
        static::restored(fn (self $generus) => MemberAccounts::setStatus('generus_id', $generus->id, 'active'));
    }

    protected function casts(): array
    {
        return [
            'birth_date' => 'date',
            'birth_order' => 'integer',
            'sibling_count' => 'integer',
        ];
    }

    /**
     * Limit to generus whose active placement falls inside the user's role scopes (any role
     * granting view or manage access). Anonymous guests carry no placement scope, so they see
     * everything the guest role's view permission already exposes at the route level.
     */
    #[Scope]
    protected function visibleTo(Builder $query, ?User $user): void
    {
        $permissions = ['view-generus', self::MANAGE_PERMISSION];

        if ($user === null || $user->hasGlobalAccessAny($permissions)) {
            return;
        }

        $placementIds = $user->scopedPlacementIdsAny($permissions);

        if ($placementIds === []) {
            $query->whereRaw('1 = 0');

            return;
        }

        $query->inPlacement($placementIds);
    }

    /**
     * Limit to generus currently placed in the given columns, e.g. `['group_id' => [3, 7]]`.
     *
     * @param  array<string, list<int>>  $placementIds
     */
    #[Scope]
    protected function inPlacement(Builder $query, array $placementIds): void
    {
        $query->whereHas('assignments', function (Builder $assignments) use ($placementIds): void {
            $assignments->whereNull('ended_at')
                ->where(function (Builder $placement) use ($placementIds): void {
                    foreach ($placementIds as $column => $ids) {
                        $placement->orWhereIn($column, $ids);
                    }
                });
        });
    }

    /**
     * RFID readers report UIDs in varying case and spacing; store and compare one canonical form.
     */
    public static function normalizeRfidUid(?string $uid): ?string
    {
        $normalized = strtoupper(preg_replace('/\s+/', '', (string) $uid));

        return $normalized === '' ? null : $normalized;
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(GenerusAssignment::class);
    }

    public function teachers(): BelongsToMany
    {
        return $this->belongsToMany(Teacher::class, 'teacher_generus')->withTimestamps();
    }

    public function guardians(): BelongsToMany
    {
        return $this->belongsToMany(Guardian::class, 'generus_guardian')->withTimestamps();
    }

    public function schoolGrade(): BelongsTo
    {
        return $this->belongsTo(ClassGrade::class, 'school_grade_id');
    }

    public function learningClass(): BelongsTo
    {
        return $this->belongsTo(ClassGrade::class, 'learning_class_id');
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
