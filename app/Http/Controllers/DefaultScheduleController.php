<?php

namespace App\Http\Controllers;

use App\Models\AcademicYear;
use App\Models\DefaultSchedule;
use App\Models\DefaultScheduleException;
use App\Models\Level;
use App\Models\Semester;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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
        $academicYearId = $request->integer('academic_year_id') ?: null;
        $semesterId = $request->integer('semester_id') ?: null;

        $schedule = ($levelId && $academicYearId && $semesterId)
            ? DefaultSchedule::with('exceptions')
                ->where('level_id', $levelId)
                ->where('academic_year_id', $academicYearId)
                ->where('semester_id', $semesterId)
                ->first()
            : null;

        return view('default-schedules.index', [
            'levels' => Level::where('is_active', true)->orderBy('sort_order')->get(),
            'academicYears' => AcademicYear::where('is_active', true)->get(),
            'semesters' => Semester::where('is_active', true)->get(),
            'days' => self::DAYS,
            'levelId' => $levelId,
            'academicYearId' => $academicYearId,
            'semesterId' => $semesterId,
            'schedule' => $schedule,
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'level_id' => ['required', 'exists:levels,id'],
            'academic_year_id' => ['required', 'exists:academic_years,id'],
            'semester_id' => ['required', 'exists:semesters,id'],
            'days_of_week' => ['required', 'array', 'min:1'],
            'days_of_week.*' => ['integer', 'min:1', 'max:7'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'start_time' => ['nullable', 'date_format:H:i'],
            'end_time' => ['nullable', 'date_format:H:i'],
        ]);

        DefaultSchedule::updateOrCreate(
            [
                'level_id' => $validated['level_id'],
                'academic_year_id' => $validated['academic_year_id'],
                'semester_id' => $validated['semester_id'],
            ],
            [
                'days_of_week' => array_values(array_map('intval', $validated['days_of_week'])),
                'start_date' => $validated['start_date'],
                'end_date' => $validated['end_date'],
                'start_time' => $validated['start_time'] ?? null,
                'end_time' => $validated['end_time'] ?? null,
            ],
        );

        return redirect()->route('default-schedules.index', $request->only(['level_id', 'academic_year_id', 'semester_id']))
            ->with('success', 'Jadwal KBM Default berhasil disimpan.');
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

        return redirect()->route('default-schedules.index', [
            'level_id' => $defaultSchedule->level_id,
            'academic_year_id' => $defaultSchedule->academic_year_id,
            'semester_id' => $defaultSchedule->semester_id,
        ])->with('success', 'Tanggal libur berhasil ditambahkan.');
    }

    public function destroyException(DefaultScheduleException $defaultScheduleException): RedirectResponse
    {
        $defaultSchedule = $defaultScheduleException->defaultSchedule;
        $defaultScheduleException->delete();

        return redirect()->route('default-schedules.index', [
            'level_id' => $defaultSchedule->level_id,
            'academic_year_id' => $defaultSchedule->academic_year_id,
            'semester_id' => $defaultSchedule->semester_id,
        ])->with('success', 'Tanggal libur berhasil dihapus.');
    }
}
