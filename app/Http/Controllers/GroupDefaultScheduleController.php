<?php

namespace App\Http\Controllers;

use App\Models\GroupDefaultSchedule;
use App\Models\GroupDefaultScheduleException;
use App\Models\Level;
use App\Support\SemesterResolver;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class GroupDefaultScheduleController extends Controller
{
    public const PERMISSION = 'manage-own-default-schedule';

    public function index(Request $request): View
    {
        $groupId = $this->ownGroupId($request);
        $allowedLevels = $this->allowedLevels($request);
        $levelId = $request->integer('level_id') ?: null;

        if ($levelId !== null && ! $allowedLevels->contains('id', $levelId)) {
            $levelId = null;
        }

        $schedules = ($groupId && $levelId)
            ? GroupDefaultSchedule::with(['exceptions', 'academicYear', 'semester'])
                ->where('group_id', $groupId)
                ->where('level_id', $levelId)
                ->get()
                ->sortByDesc('start_date')
                ->values()
            : collect();

        return view('group-default-schedules.index', [
            'levels' => $allowedLevels,
            'days' => DefaultScheduleController::DAYS,
            'levelId' => $levelId,
            'schedules' => $schedules,
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $groupId = $this->ownGroupIdOrAbort($request);
        $allowedLevelIds = $this->allowedLevels($request)->pluck('id')->all();

        $validated = $request->validate([
            'level_id' => ['required', 'exists:levels,id', Rule::in($allowedLevelIds)],
            'days_of_week' => ['required', 'array', 'min:1'],
            'days_of_week.*' => ['integer', 'min:1', 'max:7'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'start_time' => ['nullable', 'date_format:H:i'],
            'end_time' => ['nullable', 'date_format:H:i'],
        ]);

        $semester = SemesterResolver::resolveFor(Carbon::parse($validated['start_date']));

        if (! $semester) {
            return back()->withInput()->withErrors([
                'start_date' => 'Tanggal mulai tidak berada dalam periode semester mana pun. Atur dulu tanggal mulai & selesai semester di Master Data > Semester.',
            ]);
        }

        GroupDefaultSchedule::updateOrCreate(
            [
                'group_id' => $groupId,
                'level_id' => $validated['level_id'],
                'academic_year_id' => $semester->academic_year_id,
                'semester_id' => $semester->id,
            ],
            [
                'days_of_week' => array_values(array_map('intval', $validated['days_of_week'])),
                'start_date' => $validated['start_date'],
                'end_date' => $validated['end_date'],
                'start_time' => $validated['start_time'] ?? null,
                'end_time' => $validated['end_time'] ?? null,
            ],
        );

        return redirect()->route('group-default-schedules.index', ['level_id' => $validated['level_id']])
            ->with('success', "Jadwal KBM Default Kelompok berhasil disimpan untuk {$semester->name} ({$semester->academicYear->name}).");
    }

    public function storeException(Request $request, GroupDefaultSchedule $groupDefaultSchedule): RedirectResponse
    {
        $this->authorizeOwnSchedule($request, $groupDefaultSchedule);

        $validated = $request->validate([
            'exception_date' => ['required', 'date'],
            'reason' => ['nullable', 'string', 'max:255'],
        ]);

        $groupDefaultSchedule->exceptions()->firstOrCreate(
            ['exception_date' => $validated['exception_date']],
            ['reason' => $validated['reason'] ?? null],
        );

        return redirect()->route('group-default-schedules.index', ['level_id' => $groupDefaultSchedule->level_id])
            ->with('success', 'Tanggal libur berhasil ditambahkan.');
    }

    public function destroyException(Request $request, GroupDefaultScheduleException $groupDefaultScheduleException): RedirectResponse
    {
        $groupDefaultSchedule = $groupDefaultScheduleException->groupDefaultSchedule;
        $this->authorizeOwnSchedule($request, $groupDefaultSchedule);

        $groupDefaultScheduleException->delete();

        return redirect()->route('group-default-schedules.index', ['level_id' => $groupDefaultSchedule->level_id])
            ->with('success', 'Tanggal libur berhasil dihapus.');
    }

    private function ownGroupId(Request $request): ?int
    {
        $user = $request->user();
        $groupIds = $user?->scopedPlacementIds(self::PERMISSION)['group_id'] ?? [];

        return $groupIds[0] ?? $user?->teacher?->group_id ?? null;
    }

    /**
     * Levels this user may set a group default schedule for: every active level for a
     * non-teacher (Kelompok executor), or only the levels of the classes a teacher is
     * assigned to (every active level when the teacher has no specific class assignment).
     *
     * @return Collection<int, Level>
     */
    private function allowedLevels(Request $request): Collection
    {
        $levels = Level::where('is_active', true)->orderBy('sort_order')->get();

        $teacher = $request->user()?->teacher;

        if ($teacher === null) {
            return $levels;
        }

        $levelIds = $teacher->classGrades->pluck('level_id')->filter()->unique();

        return $levelIds->isEmpty() ? $levels : $levels->whereIn('id', $levelIds);
    }

    private function ownGroupIdOrAbort(Request $request): int
    {
        $groupId = $this->ownGroupId($request);

        abort_if($groupId === null, 403, 'Anda belum ditempatkan di kelompok manapun.');

        return $groupId;
    }

    private function authorizeOwnSchedule(Request $request, GroupDefaultSchedule $groupDefaultSchedule): void
    {
        abort_unless($groupDefaultSchedule->group_id === $this->ownGroupId($request), 403);
    }
}
