<?php

namespace App\Http\Controllers;

use App\Models\DefaultSchedule;
use App\Models\DefaultScheduleException;
use App\Models\Level;
use App\Models\Semester;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

class DefaultScheduleController extends Controller
{
    /**
     * @var array<int, string>
     */
    public const DAYS = [1 => 'Senin', 2 => 'Selasa', 3 => 'Rabu', 4 => 'Kamis', 5 => 'Jumat', 6 => 'Sabtu', 7 => 'Minggu'];

    public function index(Request $request): View
    {
        $levelId = $request->integer('level_id') ?: null;

        $schedules = $levelId
            ? DefaultSchedule::with(['exceptions', 'academicYear', 'semester'])
                ->where('level_id', $levelId)
                ->get()
                ->sortByDesc('start_date')
                ->values()
            : collect();

        return view('default-schedules.index', [
            'levels' => Level::where('is_active', true)->orderBy('sort_order')->get(),
            'days' => self::DAYS,
            'levelId' => $levelId,
            'schedules' => $schedules,
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'level_id' => ['required', 'exists:levels,id'],
            'days_of_week' => ['required', 'array', 'min:1'],
            'days_of_week.*' => ['integer', 'min:1', 'max:7'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'start_time' => ['nullable', 'date_format:H:i'],
            'end_time' => ['nullable', 'date_format:H:i'],
        ]);

        $semester = $this->resolveSemesterFor(Carbon::parse($validated['start_date']));

        if (! $semester) {
            return back()->withInput()->withErrors([
                'start_date' => 'Tanggal mulai tidak berada dalam periode semester mana pun. Atur dulu tanggal mulai & selesai semester di Master Data > Semester.',
            ]);
        }

        DefaultSchedule::updateOrCreate(
            [
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

        return redirect()->route('default-schedules.index', ['level_id' => $validated['level_id']])
            ->with('success', "Jadwal KBM Default berhasil disimpan untuk {$semester->name} ({$semester->academicYear->name}).");
    }

    /**
     * The semester whose configured date range covers $date, preferring the currently active one.
     */
    private function resolveSemesterFor(Carbon $date): ?Semester
    {
        return Semester::with('academicYear')
            ->whereNotNull('start_date')
            ->whereNotNull('end_date')
            ->whereDate('start_date', '<=', $date)
            ->whereDate('end_date', '>=', $date)
            ->orderByDesc('is_active')
            ->first();
    }

    public function storeException(Request $request, DefaultSchedule $defaultSchedule): RedirectResponse
    {
        $validated = $request->validate([
            'exception_date' => ['required', 'date'],
            'reason' => ['nullable', 'string', 'max:255'],
        ]);

        $defaultSchedule->exceptions()->firstOrCreate(
            ['exception_date' => $validated['exception_date']],
            ['reason' => $validated['reason'] ?? null],
        );

        return redirect()->route('default-schedules.index', ['level_id' => $defaultSchedule->level_id])
            ->with('success', 'Tanggal libur berhasil ditambahkan.');
    }

    public function destroyException(DefaultScheduleException $defaultScheduleException): RedirectResponse
    {
        $defaultSchedule = $defaultScheduleException->defaultSchedule;
        $defaultScheduleException->delete();

        return redirect()->route('default-schedules.index', ['level_id' => $defaultSchedule->level_id])
            ->with('success', 'Tanggal libur berhasil dihapus.');
    }
}
