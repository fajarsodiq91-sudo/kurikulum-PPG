<?php

namespace App\Http\Controllers;

use App\Models\Generus;
use App\Models\LearningSession;
use App\Models\SessionAttendance;
use App\Support\StudentScope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class SessionAttendanceController extends Controller
{
    public function index(): View
    {
        return view('session-attendances.index', [
            'attendances' => StudentScope::limit(SessionAttendance::with(['learningSession', 'generus'])->latest(), $this->studentIds())->paginate(25),
            ...$this->formOptions(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        SessionAttendance::create($this->validateAttendance($request));

        return redirect()->route('session-attendances.index')->with('success', 'Presensi berhasil ditambahkan.');
    }

    public function edit(SessionAttendance $sessionAttendance): View
    {
        $this->authorizeRecord($sessionAttendance);

        return view('session-attendances.edit', [
            'attendance' => $sessionAttendance,
            ...$this->formOptions($sessionAttendance),
        ]);
    }

    public function update(Request $request, SessionAttendance $sessionAttendance): RedirectResponse
    {
        $this->authorizeRecord($sessionAttendance);

        $sessionAttendance->update($this->validateAttendance($request, $sessionAttendance));

        return redirect()->route('session-attendances.index')->with('success', 'Presensi berhasil diperbarui.');
    }

    public function destroy(SessionAttendance $sessionAttendance): RedirectResponse
    {
        $this->authorizeRecord($sessionAttendance);

        $sessionAttendance->delete();

        return redirect()->route('session-attendances.index')->with('success', 'Presensi berhasil dihapus.');
    }

    /**
     * Active generus, plus the one already linked to the attendance being edited.
     *
     * @return array<string, mixed>
     */
    private function formOptions(?SessionAttendance $attendance = null): array
    {
        return [
            'sessions' => LearningSession::with('teacher')->latest()->get(),
            'generus' => StudentScope::limit(Generus::where('status', 'active')
                ->when($attendance, fn (Builder $query) => $query->orWhere('id', $attendance->generus_id)), $this->studentIds(), 'id')
                ->get(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function validateAttendance(Request $request, ?SessionAttendance $attendance = null): array
    {
        return $request->validate([
            'learning_session_id' => ['required', 'exists:learning_sessions,id'],
            'generus_id' => [
                'required',
                StudentScope::existsRule($request->user(), 'manage-learning-attendance'),
                Rule::unique('session_attendances')
                    ->where('learning_session_id', $request->input('learning_session_id'))
                    ->ignore($attendance?->id),
            ],
            'status' => ['required', 'string', 'max:50'],
            'notes' => ['nullable', 'string'],
        ], [
            'generus_id.unique' => 'Presensi generus ini untuk sesi tersebut sudah dicatat.',
        ]);
    }

    private function studentIds(): ?array
    {
        return StudentScope::ids(request()->user(), 'manage-learning-attendance');
    }

    private function authorizeRecord(SessionAttendance $attendance): void
    {
        StudentScope::authorize(request()->user(), 'manage-learning-attendance', $attendance->generus_id);
    }
}
