<?php

namespace App\Http\Controllers;

use App\Models\Generus;
use App\Models\LearningSession;
use App\Models\SessionAttendance;
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
            'attendances' => SessionAttendance::with(['learningSession', 'generus'])->latest()->paginate(25),
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
        return view('session-attendances.edit', [
            'attendance' => $sessionAttendance,
            ...$this->formOptions($sessionAttendance),
        ]);
    }

    public function update(Request $request, SessionAttendance $sessionAttendance): RedirectResponse
    {
        $sessionAttendance->update($this->validateAttendance($request, $sessionAttendance));

        return redirect()->route('session-attendances.index')->with('success', 'Presensi berhasil diperbarui.');
    }

    public function destroy(SessionAttendance $sessionAttendance): RedirectResponse
    {
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
            'generus' => Generus::where('status', 'active')
                ->when($attendance, fn (Builder $query) => $query->orWhere('id', $attendance->generus_id))
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
                'exists:generus,id',
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
}
