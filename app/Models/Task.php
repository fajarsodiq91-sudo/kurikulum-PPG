<?php

namespace App\Models;

use App\Support\TaskHierarchy;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Task extends Model
{
    /**
     * @var list<string>
     */
    public const STATUSES = ['todo', 'in_progress', 'done'];

    /**
     * @var list<string>
     */
    public const PRIORITIES = ['low', 'medium', 'high'];

    protected $fillable = [
        'title',
        'description',
        'bucket',
        'assigned_by_id',
        'priority',
        'status',
        'due_date',
        'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'due_date' => 'date',
            'completed_at' => 'datetime',
        ];
    }

    public function assignedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_by_id');
    }

    public function assignees(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'task_assignees')->withTimestamps();
    }

    /**
     * Tasks the user created, was assigned, or has hierarchical oversight of (their own
     * organisation node or anything beneath it - see TaskHierarchy::downwardReachIds()).
     *
     * @param  Builder<static>  $query
     */
    #[Scope]
    protected function visibleTo(Builder $query, User $user): void
    {
        $downwardIds = TaskHierarchy::downwardReachIds($user);

        $query->where(function (Builder $scoped) use ($user, $downwardIds): void {
            $scoped->where('assigned_by_id', $user->id)
                ->orWhereHas('assignees', fn (Builder $assignees) => $assignees->whereKey($user->id));

            if ($downwardIds === null) {
                $scoped->orWhereRaw('1 = 1');
            } elseif ($downwardIds !== []) {
                $scoped->orWhereHas('assignees', fn (Builder $assignees) => $assignees->whereIn('users.id', $downwardIds));
            }
        });
    }

    /**
     * Full control (edit, reassign, delete): the creator, or anyone whose hierarchical
     * downward reach covers every current assignee.
     */
    public function isManageableBy(User $user): bool
    {
        if ($this->assigned_by_id === $user->id) {
            return true;
        }

        $downwardIds = TaskHierarchy::downwardReachIds($user);

        if ($downwardIds === null) {
            return true;
        }

        $assigneeIds = $this->assignees->pluck('id');

        return $assigneeIds->isNotEmpty() && $assigneeIds->diff($downwardIds)->isEmpty();
    }

    /**
     * Status may additionally be updated by anyone the task was assigned to.
     */
    public function isUpdatableStatusBy(User $user): bool
    {
        return $this->isManageableBy($user) || $this->assignees->contains('id', $user->id);
    }
}
