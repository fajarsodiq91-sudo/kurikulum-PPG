<?php

namespace App\Http\Controllers;

use App\Models\Musyawaroh;
use App\Models\Notulen;
use App\Models\Task;
use App\Models\User;
use App\Notifications\TaskNotification;
use App\Notifications\TaskStatusChanged;
use App\Support\TaskHierarchy;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

class TaskController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();

        $tasks = Task::visibleTo($user)
            ->with(['assignedBy', 'assignees', 'teams'])
            ->orderByRaw('due_date is null, due_date')
            ->latest('id')
            ->get();

        $tasksByBucket = $tasks
            ->groupBy(fn (Task $task): string => $task->bucket ?: 'Tanpa Kategori')
            ->sortBy(fn (Collection $group, string $bucket): array => [$bucket === 'Tanpa Kategori' ? 1 : 0, $bucket]);

        $notulens = Notulen::visibleTo($user)
            ->with(['createdBy', 'musyawaroh'])
            ->latest('meeting_date')
            ->latest('id')
            ->get();

        $musyawarohs = Musyawaroh::visibleTo($user)
            ->latest('meeting_date')
            ->latest('id')
            ->get();

        return view('tasks.index', [
            'tasks' => $tasks,
            'tasksByStatus' => $tasks->groupBy('status'),
            'tasksByBucket' => $tasksByBucket,
            'assigneeOptions' => $tasks->flatMap(fn (Task $task) => $task->assignees)->unique('id')->sortBy('name')->values(),
            'recipients' => TaskHierarchy::assignableRecipients($user),
            'teams' => TaskHierarchy::assignableTeams($user),
            'bucketSuggestions' => TaskHierarchy::bucketSuggestionsFor($user),
            'notulens' => $notulens,
            'musyawarohs' => $musyawarohs,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validateTask($request, $request->user());
        $notulen = $this->resolveNotulenForTask($request, $validated);

        $task = Task::create([
            'title' => $validated['title'],
            'description' => $validated['description'] ?? null,
            'bucket' => $validated['bucket'] ?? null,
            'link' => $validated['link'] ?? null,
            'attachment' => $this->storeAttachment($validated['attachment'] ?? null),
            'priority' => $validated['priority'],
            'due_date' => $validated['due_date'] ?? now()->addDays(Task::DEFAULT_DUE_DATE_DAYS)->toDateString(),
            'assigned_by_id' => $request->user()->id,
            'status' => 'todo',
            'notulen_id' => $notulen?->id,
        ]);

        $this->syncAssigneesAndTeams($task, $validated);

        try {
            Notification::send($task->assignees, new TaskNotification($task, 'created', $request->user()));
        } catch (Throwable $e) {
            Log::error('Gagal mengirim notifikasi tugas baru ke penerima.', [
                'task_id' => $task->id,
                'exception' => $e,
            ]);
        }

        if ($notulen !== null) {
            return redirect()->route('musyawaroh.show', $notulen->musyawaroh)->with('success', 'Tugas berhasil dibuat dari notulen.');
        }

        return redirect()->route('tasks.index')->with('success', 'Tugas berhasil dibuat.');
    }

    public function edit(Request $request, Task $task): View
    {
        abort_unless($task->isEditableBy($request->user()), 403);

        return view('tasks.edit', [
            'task' => $task->load(['assignees', 'teams']),
            'recipients' => TaskHierarchy::assignableRecipients($task->assignedBy),
            'teams' => TaskHierarchy::assignableTeams($task->assignedBy),
            'bucketSuggestions' => TaskHierarchy::bucketSuggestionsFor($request->user()),
        ]);
    }

    public function update(Request $request, Task $task): RedirectResponse
    {
        abort_unless($task->isEditableBy($request->user()), 403);

        $actor = $request->user();
        $validated = $this->validateTask($request, $task->assignedBy);
        $previousAssigneeIds = $task->assignees()->pluck('users.id')->all();
        $oldAttachmentPath = $task->attachment;
        $newAttachmentPath = $this->storeAttachment($validated['attachment'] ?? null);

        $task->update([
            'title' => $validated['title'],
            'description' => $validated['description'] ?? null,
            'bucket' => $validated['bucket'] ?? null,
            'link' => $validated['link'] ?? null,
            'priority' => $validated['priority'],
            'due_date' => $validated['due_date'] ?? null,
            ...($newAttachmentPath !== null ? ['attachment' => $newAttachmentPath] : []),
        ]);

        if ($newAttachmentPath !== null && $oldAttachmentPath !== null) {
            Storage::delete($oldAttachmentPath);
        }

        $resolvedAssigneeIds = $this->syncAssigneesAndTeams($task, $validated);
        $task->load('assignees');

        $newlyAssignedIds = array_diff($resolvedAssigneeIds, $previousAssigneeIds);
        $removedAssigneeIds = array_diff($previousAssigneeIds, $resolvedAssigneeIds);

        try {
            $notifiableAssignees = $task->assignees->reject(fn (User $assignee): bool => $assignee->is($actor));
            Notification::send(
                $notifiableAssignees->whereIn('id', $newlyAssignedIds),
                new TaskNotification($task, 'created', $actor)
            );
            Notification::send(
                $notifiableAssignees->whereNotIn('id', $newlyAssignedIds),
                new TaskNotification($task, 'updated', $actor)
            );

            if ($removedAssigneeIds !== []) {
                Notification::send(User::whereIn('id', $removedAssigneeIds)->get(), new TaskNotification($task, 'unassigned', $actor));
            }

            if (! $task->assignedBy->is($actor)) {
                $task->assignedBy->notify(new TaskNotification($task, 'updated', $actor));
            }
        } catch (Throwable $e) {
            Log::error('Gagal mengirim notifikasi perubahan tugas.', [
                'task_id' => $task->id,
                'exception' => $e,
            ]);
        }

        return redirect()->route('tasks.index')->with('success', 'Tugas berhasil diperbarui.');
    }

    public function updateStatus(Request $request, Task $task): RedirectResponse
    {
        abort_unless($task->isStatusUpdatableBy($request->user()), 403);

        $validated = $request->validate([
            'status' => ['required', Rule::in(Task::STATUSES)],
        ]);

        $task->update([
            'status' => $validated['status'],
            'completed_at' => $validated['status'] === 'done' ? now() : null,
        ]);

        try {
            if (! $task->assignedBy->is($request->user())) {
                $task->assignedBy->notify(new TaskStatusChanged($task, $request->user()));
            }
        } catch (Throwable $e) {
            Log::error('Gagal mengirim notifikasi perubahan status tugas.', [
                'task_id' => $task->id,
                'exception' => $e,
            ]);
        }

        return back()->with('success', 'Status tugas berhasil diperbarui.');
    }

    public function destroy(Request $request, Task $task): RedirectResponse
    {
        abort_unless($task->isDeletableBy($request->user()), 403);

        if ($task->attachment !== null) {
            Storage::delete($task->attachment);
        }

        $task->delete();

        return redirect()->route('tasks.index')->with('success', 'Tugas berhasil dihapus.');
    }

    /**
     * A task's attachment - viewable by anyone the task is already visible to (its creator or
     * any of its assignees), mirroring Task::isEditableBy()'s reach.
     */
    public function attachment(Request $request, Task $task): StreamedResponse
    {
        abort_unless($task->isEditableBy($request->user()), 403);
        abort_if(blank($task->attachment) || ! Storage::exists($task->attachment), 404);

        return Storage::response($task->attachment);
    }

    /**
     * The assignee picker's avatar image - only for a recipient the current user could actually
     * assign a task to, and only when their teacher profile has a photo on file.
     */
    public function recipientPhoto(Request $request, User $recipient): StreamedResponse
    {
        abort_unless(TaskHierarchy::assignableRecipients($request->user())->contains('id', $recipient->id), 404);

        $photo = $recipient->teacher?->photo;

        abort_if(blank($photo) || ! Storage::exists($photo), 404);

        return Storage::response($photo);
    }

    /**
     * Assignees and teams are always validated against who/what the task's creator could
     * reach, not the current editor - so an assignee editing a task they didn't create can't
     * expand its audience, and resubmitting the existing (unchanged) list never breaks.
     *
     * A task needs at least one named assignee or one team, and the two combined must not
     * resolve to an empty set of recipients (e.g. a team with no active members right now).
     *
     * @return array<string, mixed>
     */
    private function validateTask(Request $request, User $recipientScopeUser): array
    {
        $recipientIds = TaskHierarchy::assignableRecipients($recipientScopeUser)->pluck('id')->all();
        $teamKeys = TaskHierarchy::assignableTeams($recipientScopeUser)->pluck('key')->all();

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'bucket' => ['nullable', 'string', 'max:255'],
            'link' => ['nullable', 'url', 'max:2048'],
            'attachment' => ['nullable', 'file', 'max:10240', 'mimes:pdf,doc,docx,xls,xlsx,ppt,pptx,jpg,jpeg,png'],
            'priority' => ['required', Rule::in(Task::PRIORITIES)],
            'due_date' => ['nullable', 'date'],
            'assignees' => ['nullable', 'array'],
            'assignees.*' => ['integer', Rule::in($recipientIds)],
            'teams' => ['nullable', 'array'],
            'teams.*' => ['string', Rule::in($teamKeys)],
            'notulen_id' => ['nullable', 'integer', Rule::exists('notulens', 'id')],
        ]);

        $validated['assignees'] = $validated['assignees'] ?? [];
        $validated['teams'] = $validated['teams'] ?? [];

        abort_if($validated['assignees'] === [] && $validated['teams'] === [], 422, 'Pilih minimal satu penerima tugas atau tim.');

        $resolvedIds = collect([...$validated['assignees'], ...TaskHierarchy::teamMemberIds($validated['teams'])])->unique()->values();

        abort_if($resolvedIds->isEmpty(), 422, 'Tim yang dipilih tidak memiliki anggota aktif saat ini.');

        return $validated;
    }

    /**
     * Expands the task's chosen teams into their current active members, merges them with the
     * explicitly picked assignees, syncs both the assignees pivot (which is all Task's
     * visibility/edit/status rules ever look at) and the task_teams bookkeeping rows, and
     * returns the resolved assignee ids so the caller can diff old vs new for notifications.
     *
     * @param  array<string, mixed>  $validated
     * @return list<int>
     */
    private function syncAssigneesAndTeams(Task $task, array $validated): array
    {
        $teams = TaskHierarchy::assignableTeams($task->assignedBy)
            ->whereIn('key', $validated['teams'])
            ->values();

        $teamMemberIds = TaskHierarchy::teamMemberIds($teams->pluck('key')->all());
        $resolvedIds = collect([...$validated['assignees'], ...$teamMemberIds])->unique()->values()->all();

        $task->assignees()->sync($resolvedIds);

        $task->teams()->delete();
        $task->teams()->createMany($teams->map(fn (array $team): array => [
            'dapukan_id' => $team['dapukan_id'],
            'scope_type' => $team['scope_type'],
            'scope_id' => $team['scope_id'],
            'label' => $team['label'],
        ])->all());

        return $resolvedIds;
    }

    /**
     * Resolves the notulen a new task is being created from, if any - only when it is still
     * unclaimed (no task of its own yet) and visible to the current user's musyawaroh reach.
     *
     * @param  array<string, mixed>  $validated
     */
    private function resolveNotulenForTask(Request $request, array $validated): ?Notulen
    {
        if (! isset($validated['notulen_id'])) {
            return null;
        }

        $notulen = Notulen::with('musyawaroh')->findOrFail($validated['notulen_id']);

        abort_if($notulen->task !== null, 422, 'Notulen ini sudah memiliki tugas.');
        abort_unless($notulen->isCheckableBy($request->user()), 403);

        return $notulen;
    }

    private function storeAttachment(?UploadedFile $attachment): ?string
    {
        return $attachment?->store(Task::ATTACHMENT_DIRECTORY);
    }
}
