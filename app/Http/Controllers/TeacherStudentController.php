<?php

namespace App\Http\Controllers;

use App\Models\Generus;
use App\Models\Teacher;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TeacherStudentController extends Controller
{
    public function mine(Request $request): View
    {
        return $this->form($this->ownTeacher($request), route('my-students.update'));
    }

    public function updateMine(Request $request): RedirectResponse
    {
        return $this->save($request, $this->ownTeacher($request), route('my-students.edit'));
    }

    public function edit(Request $request, Teacher $teacher): View
    {
        $this->ensureVisible($request, $teacher);

        return $this->form($teacher, route('teachers.students.update', $teacher));
    }

    public function update(Request $request, Teacher $teacher): RedirectResponse
    {
        $this->ensureVisible($request, $teacher);

        return $this->save($request, $teacher, route('teachers.students.edit', $teacher));
    }

    private function ensureVisible(Request $request, Teacher $teacher): void
    {
        abort_unless(Teacher::visibleTo($request->user())->whereKey($teacher->id)->exists(), 404);
    }

    private function ownTeacher(Request $request): Teacher
    {
        return $request->user()->teacher ?? abort(404);
    }

    private function form(Teacher $teacher, string $action): View
    {
        return view('teachers.students', [
            'teacher' => $teacher->load('group.village'),
            'action' => $action,
            'candidates' => $this->candidates($teacher),
            'selectedIds' => $teacher->students()->pluck('generus.id')->all(),
        ]);
    }

    private function save(Request $request, Teacher $teacher, string $redirectTo): RedirectResponse
    {
        $validated = $request->validate([
            'student_ids' => ['nullable', 'array'],
            'student_ids.*' => ['integer'],
        ]);

        $allowedIds = $this->candidates($teacher)->pluck('id')->all();
        $teacher->students()->sync(array_values(array_intersect($validated['student_ids'] ?? [], $allowedIds)));

        return redirect($redirectTo)->with('success', 'Daftar murid berhasil disimpan.');
    }

    /**
     * Active generus placed in the teacher's group, plus anyone already ticked.
     *
     * @return Collection<int, Generus>
     */
    private function candidates(Teacher $teacher): Collection
    {
        $linkedIds = $teacher->students()->pluck('generus.id');

        return Generus::query()
            ->where(function (Builder $query) use ($teacher, $linkedIds): void {
                $query->whereIn('id', $linkedIds);

                if ($teacher->group_id !== null) {
                    $query->orWhere(fn (Builder $inGroup) => $inGroup
                        ->where('status', 'active')
                        ->whereHas('assignments', fn (Builder $assignments) => $assignments
                            ->whereNull('ended_at')
                            ->where('group_id', $teacher->group_id)));
                }
            })
            ->orderBy('full_name')
            ->get();
    }
}
