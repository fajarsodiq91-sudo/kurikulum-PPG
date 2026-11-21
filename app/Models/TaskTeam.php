<?php

namespace App\Models;

use App\Support\TaskHierarchy;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Records that a task was assigned to an entire jabatan/team (a Dapukan within a specific
 * organisational node, e.g. "Bidang Kurikulum" at the Daerah level) rather than - or in
 * addition to - named individuals. The task's actual recipients still land in the regular
 * task_assignees pivot (resolved from the team's current members at save time), so Task's
 * visibility/edit/status rules never need to know teams exist; this table only remembers
 * which team(s) were picked, for display and for re-selecting them when the task is edited.
 */
class TaskTeam extends Model
{
    protected $fillable = [
        'task_id',
        'dapukan_id',
        'scope_type',
        'scope_id',
        'label',
    ];

    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class);
    }

    public function dapukan(): BelongsTo
    {
        return $this->belongsTo(Dapukan::class);
    }

    /**
     * The composite key a submitted form identifies this team by - see
     * {@see TaskHierarchy::assignableTeams()}.
     */
    public function key(): string
    {
        return $this->dapukan_id.'|'.$this->scope_type.'|'.($this->scope_id ?? '');
    }
}
