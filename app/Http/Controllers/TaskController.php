<?php

namespace App\Http\Controllers;

use App\Models\Task;
use App\Models\User;
use App\Notifications\TaskNotification;
use App\Notifications\TaskStatusChanged;
use App\Support\TaskHierarchy;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class TaskController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();

        $tasks = Task::visibleTo($user)
            ->with(['assignedBy', 'assignees'])
            ->orderByRaw('due_date is null, due_date')
            ->latest('id')
            ->get()
            ->groupBy('status');

        return view('tasks.index', [
            'tasksByStatus' => $tasks,
            'recipients' => TaskHierarchy::assignableRecipients($user),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validateTask($request, $request->user());

        $task = Task::create([
            'title' => $validated['title'],
            'description' => $validated['description'] ?? null,
            'bucket' => $validated['bucket'] ?? null,
            'priority' => $validated['priority'],
            'due_date' => $validated['due_date'] ?? null,
            'assigned_by_id' => $request->user()->id,
            'status' => 'todo',
        ]);

        $task->assignees()->sync($validated['assignees']);

        Notification::send($task->assignees, new TaskNotification($task, 'created'));

        return redirect()->route('tasks.index')->with('success', 'Tugas berhasil dibuat.');
    }

    public function edit(Request $request, Task $task): View
    {
        abort_unless($task->isEditableBy($request->user()), 403);

        return view('tasks.edit', [
            'task' => $task->load('assignees'),
            'recipients' => TaskHierarchy::assignableRecipients($task->assignedBy),
        ]);
    }

    public function update(Request $request, Task $task): RedirectResponse
    {
        abort_unless($task->isEditableBy($request->user()), 403);

        $validated = $this->validateTask($request, $task->assignedBy);

        $task->update([
            'title' => $validated['title'],
            'description' => $validated['description'] ?? null,
            'bucket' => $validated['bucket'] ?? null,
            'priority' => $validated['priority'],
            'due_date' => $validated['due_date'] ?? null,
        ]);

        $task->assignees()->sync($validated['assignees']);

        Notification::send($task->assignees, new TaskNotification($task, 'updated'));

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

        if (! $task->assignedBy->is($request->user())) {
            $task->assignedBy->notify(new TaskStatusChanged($task, $request->user()));
        }

        return back()->with('success', 'Status tugas berhasil diperbarui.');
    }

    public function destroy(Request $request, Task $task): RedirectResponse
    {
        abort_unless($task->isDeletableBy($request->user()), 403);

        $task->delete();

        return redirect()->route('tasks.index')->with('success', 'Tugas berhasil dihapus.');
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
            'priority' => ['required', Rule::in(Task::PRIORITIES)],
            'due_date' => ['nullable', 'date'],
            'assignees' => ['required', 'array', 'min:1'],
            'assignees.*' => ['integer', Rule::in($recipientIds)],
        ]);
    }
}
