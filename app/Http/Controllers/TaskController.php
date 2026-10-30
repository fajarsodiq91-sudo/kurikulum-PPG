<?php

namespace App\Http\Controllers;

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
            ->with(['assignedBy', 'assignees'])
            ->orderByRaw('due_date is null, due_date')
            ->latest('id')
            ->get();

        $tasksByBucket = $tasks
            ->groupBy(fn (Task $task): string => $task->bucket ?: 'Tanpa Kategori')
            ->sortBy(fn (Collection $group, string $bucket): array => [$bucket === 'Tanpa Kategori' ? 1 : 0, $bucket]);

        return view('tasks.index', [
            'tasks' => $tasks,
            'tasksByStatus' => $tasks->groupBy('status'),
            'tasksByBucket' => $tasksByBucket,
            'assigneeOptions' => $tasks->flatMap(fn (Task $task) => $task->assignees)->unique('id')->sortBy('name')->values(),
            'recipients' => TaskHierarchy::assignableRecipients($user),
            'bucketSuggestions' => TaskHierarchy::bucketSuggestionsFor($user),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validateTask($request, $request->user());

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
        ]);

        $task->assignees()->sync($validated['assignees']);

        try {
            Notification::send($task->assignees, new TaskNotification($task, 'created', $request->user()));
        } catch (Throwable $e) {
            Log::error('Gagal mengirim notifikasi tugas baru ke penerima.', [
                'task_id' => $task->id,
                'exception' => $e,
            ]);
        }

        return redirect()->route('tasks.index')->with('success', 'Tugas berhasil dibuat.');
    }

    public function edit(Request $request, Task $task): View
    {
        abort_unless($task->isEditableBy($request->user()), 403);

        return view('tasks.edit', [
            'task' => $task->load('assignees'),
            'recipients' => TaskHierarchy::assignableRecipients($task->assignedBy),
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

        $task->assignees()->sync($validated['assignees']);
        $task->load('assignees');

        $newlyAssignedIds = array_diff($validated['assignees'], $previousAssigneeIds);
        $removedAssigneeIds = array_diff($previousAssigneeIds, $validated['assignees']);

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
        abort_unless($task->isEditableBy($request->user()), 403);

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
     * Assignees are always validated against who the task's creator could reach, not the
     * current editor - so an assignee editing a task they didn't create can't expand its
     * audience, and resubmitting the existing (unchanged) assignee list never breaks.
     *
     * @return array<string, mixed>
     */
    private function validateTask(Request $request, User $recipientScopeUser): array
    {
        $recipientIds = TaskHierarchy::assignableRecipients($recipientScopeUser)->pluck('id')->all();

        return $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'bucket' => ['nullable', 'string', 'max:255'],
            'link' => ['nullable', 'url', 'max:2048'],
            'attachment' => ['nullable', 'file', 'max:10240', 'mimes:pdf,doc,docx,xls,xlsx,ppt,pptx,jpg,jpeg,png'],
            'priority' => ['required', Rule::in(Task::PRIORITIES)],
            'due_date' => ['nullable', 'date'],
            'assignees' => ['required', 'array', 'min:1'],
            'assignees.*' => ['integer', Rule::in($recipientIds)],
        ]);
    }

    private function storeAttachment(?UploadedFile $attachment): ?string
    {
        return $attachment?->store(Task::ATTACHMENT_DIRECTORY);
    }
}
