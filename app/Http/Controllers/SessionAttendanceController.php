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
    /**
     * @var array<string, string>
     */
    private const STATUSES = ['present' => 'Hadir', 'late' => 'Terlambat', 'excused' => 'Izin', 'absent' => 'Absen'];

    public function index(): View
    {
        return view('session-attendances.index', [
            'attendances' => StudentScope::limit(SessionAttendance::with(['learningSession', 'generus'])->latest(), $this->studentIds())->paginate(25),
            ...$this->formOptions(),
        ]);
    }

    public function sheet(LearningSession $learningSession): View
    {
        $this->authorizeSession($learningSession);

        return view('session-attendances.sheet', [
            'session' => $learningSession->load(['teacher', 'material', 'village', 'group']),
            'roster' => $learningSession->rosterFor(request()->user())->get(),
            'recorded' => $learningSession->attendances()->get()->keyBy('generus_id'),
            'statuses' => self::STATUSES,
            'facesWithPhoto' => $learningSession->rosterFor(request()->user())->whereNotNull('photo')->count(),
        ]);
    }

    public function saveSheet(Request $request, LearningSession $learningSession): RedirectResponse
    {
        $this->authorizeSession($learningSession);

        $rosterIds = $learningSession->rosterFor(request()->user())->pluck('id')->map(fn (mixed $id): int => (int) $id)->all();

        $validated = $request->validate([
            'attendance' => ['nullable', 'array'],
            'attendance.*.status' => ['nullable', Rule::in(array_keys(self::STATUSES))],
            'attendance.*.notes' => ['nullable', 'string', 'max:1000'],
        ]);

        foreach ($validated['attendance'] ?? [] as $generusId => $entry) {
            abort_unless(in_array((int) $generusId, $rosterIds, true), 422, 'Generus tidak termasuk dalam daftar hadir sesi ini.');

            if (blank($entry['status'] ?? null)) {
                continue;
            }

            $attendance = $learningSession->attendances()->firstOrNew(['generus_id' => (int) $generusId]);
            $attendance->method = $attendance->exists && $attendance->status === $entry['status'] ? $attendance->method : SessionAttendance::METHOD_MANUAL;
            $attendance->fill(['status' => $entry['status'], 'notes' => $entry['notes'] ?? null])->save();
        }

        return redirect()->route('learning-sessions.attendance.edit', $learningSession)->with('success', 'Daftar hadir berhasil disimpan.');
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
            'sessions' => LearningSession::with('teacher')
                ->whereIn('id', LearningSession::query()->attendableBy(request()->user())->pluck('id')
                    ->when($attendance, fn ($ids) => $ids->push($attendance->learning_session_id)))
                ->latest()->get(),
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
            'learning_session_id' => [
                'required',
                Rule::exists('learning_sessions', 'id')
                    ->whereIn('id', LearningSession::query()->attendableBy($request->user())->pluck('id')),
            ],
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

    /**
     * Sessions outside the user's village or group respond as not found.
     */
    private function authorizeSession(LearningSession $session): void
    {
        abort_unless($session->isAttendableBy(request()->user()), 404);
    }

    private function authorizeRecord(SessionAttendance $attendance): void
    {
        $this->authorizeSession($attendance->learningSession);
        StudentScope::authorize(request()->user(), 'manage-learning-attendance', $attendance->generus_id);
    }
}
