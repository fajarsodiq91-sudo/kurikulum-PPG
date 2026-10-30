<?php

namespace App\Models;

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

    /**
     * Default due date offset (H+14) applied when a new task is created without one.
     */
    public const DEFAULT_DUE_DATE_DAYS = 14;

    public const ATTACHMENT_DIRECTORY = 'task-attachments';

    protected $fillable = [
        'title',
        'description',
        'bucket',
        'link',
        'attachment',
        'assigned_by_id',
        'priority',
        'status',
        'due_date',
        'completed_at',
        'due_reminder_sent_on',
    ];

    protected function casts(): array
    {
        return [
            'due_date' => 'date',
            'completed_at' => 'datetime',
            'due_reminder_sent_on' => 'date',
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
     * Tasks are internal to their creator and assignees - nobody else, regardless of where
     * they sit in the organisation hierarchy. Super Admin sees everything.
     *
     * @param  Builder<static>  $query
     */
    #[Scope]
    protected function visibleTo(Builder $query, User $user): void
    {
        if ($user->isSuperAdmin()) {
            return;
        }

        $query->where(function (Builder $scoped) use ($user): void {
            $scoped->where('assigned_by_id', $user->id)
                ->orWhereHas('assignees', fn (Builder $assignees) => $assignees->whereKey($user->id));
        });
    }

    /**
     * Editable (title, description, assignees, status, ...) by the creator or anyone the
     * task is assigned to - everyone the task is already visible to, in other words.
     */
    public function isEditableBy(User $user): bool
    {
        return $user->isSuperAdmin() || $this->assigned_by_id === $user->id || $this->assignees->contains('id', $user->id);
    }

    /**
     * Deletable only by whoever created it (or Super Admin).
     */
    public function isDeletableBy(User $user): bool
    {
        return $user->isSuperAdmin() || $this->assigned_by_id === $user->id;
    }
}
