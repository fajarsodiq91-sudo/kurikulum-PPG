<?php

namespace App\Models;

use App\Support\TaskHierarchy;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Notulen extends Model
{
    protected $fillable = [
        'title',
        'content',
        'bucket',
        'meeting_date',
        'created_by_id',
    ];

    protected function casts(): array
    {
        return [
            'meeting_date' => 'date',
        ];
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_id');
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
}
