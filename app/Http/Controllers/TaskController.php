<?php

namespace App\Http\Controllers;

use App\Models\Task;
use App\Support\TaskHierarchy;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

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
        $validated = $this->validateTask($request);

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

        return redirect()->route('tasks.index')->with('success', 'Tugas berhasil dibuat.');
    }

    public function edit(Request $request, Task $task): View
    {
        abort_unless($task->isManageableBy($request->user()), 403);

        return view('tasks.edit', [
            'task' => $task->load('assignees'),
            'recipients' => TaskHierarchy::assignableRecipients($request->user()),
        ]);
    }

    public function update(Request $request, Task $task): RedirectResponse
    {
        abort_unless($task->isManageableBy($request->user()), 403);

        $validated = $this->validateTask($request);

        $task->update([
            'title' => $validated['title'],
            'description' => $validated['description'] ?? null,
            'bucket' => $validated['bucket'] ?? null,
            'priority' => $validated['priority'],
            'due_date' => $validated['due_date'] ?? null,
        ]);

        $task->assignees()->sync($validated['assignees']);

        return redirect()->route('tasks.index')->with('success', 'Tugas berhasil diperbarui.');
    }

    public function updateStatus(Request $request, Task $task): RedirectResponse
    {
        abort_unless($task->isUpdatableStatusBy($request->user()), 403);

        $validated = $request->validate([
            'status' => ['required', Rule::in(Task::STATUSES)],
        ]);

        $task->update([
            'status' => $validated['status'],
            'completed_at' => $validated['status'] === 'done' ? now() : null,
        ]);

        return back()->with('success', 'Status tugas berhasil diperbarui.');
    }

    public function destroy(Request $request, Task $task): RedirectResponse
    {
        abort_unless($task->isManageableBy($request->user()), 403);

        $task->delete();

        return redirect()->route('tasks.index')->with('success', 'Tugas berhasil dihapus.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validateTask(Request $request): array
    {
        $recipientIds = TaskHierarchy::assignableRecipients($request->user())->pluck('id')->all();

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
