<?php

namespace App\Models;

use App\Support\TaskHierarchy;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Notulen extends Model
{
    protected $fillable = [
        'title',
        'content',
        'bucket',
        'meeting_date',
        'created_by_id',
        'musyawaroh_id',
        'is_done',
        'checked_by_id',
        'checked_at',
    ];

    protected function casts(): array
    {
        return [
            'meeting_date' => 'date',
            'is_done' => 'boolean',
            'checked_at' => 'datetime',
        ];
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_id');
    }

    public function checkedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'checked_by_id');
    }

    public function musyawaroh(): BelongsTo
    {
        return $this->belongsTo(Musyawaroh::class);
    }

    public function task(): HasOne
    {
        return $this->hasOne(Task::class);
    }

    /**
     * Visible to whoever could have attended the musyawaroh it summarises: the author's own
     * vertical reach (their branch and their ancestors), mirroring the hierarchy tasks use.
     * Super Admin sees everything.
     *
     * @param  Builder<static>  $query
     */
    #[Scope]
    protected function visibleTo(Builder $query, User $user): void
    {
        if ($user->isSuperAdmin()) {
            return;
        }

        $downward = TaskHierarchy::downwardReachIds($user);

        if ($downward === null) {
            return;
        }

        $ids = collect([...$downward, ...TaskHierarchy::upwardReachIds($user), $user->id])->unique()->values()->all();

        $query->whereIn('created_by_id', $ids);
    }

    public function isEditableBy(User $user): bool
    {
        return $user->isSuperAdmin() || $this->created_by_id === $user->id;
    }

    /**
     * Checking a notulen item off is open to anyone who could see the musyawaroh it
     * belongs to, not just its creator.
     */
    public function isCheckableBy(User $user): bool
    {
        return $this->musyawaroh !== null && $this->musyawaroh->isVisibleTo($user);
    }
}
