<?php

namespace App\Http\Controllers;

use App\Models\AcademicYear;
use App\Models\DefaultSchedule;
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

        $weeks = ($levelId && $academicYearId && $semesterId)
            ? DefaultSchedule::where('level_id', $levelId)
                ->where('academic_year_id', $academicYearId)
                ->where('semester_id', $semesterId)
                ->orderBy('week_number')
                ->get()
            : collect();

        return view('default-schedules.index', [
            'levels' => Level::where('is_active', true)->orderBy('sort_order')->get(),
            'academicYears' => AcademicYear::where('is_active', true)->get(),
            'semesters' => Semester::where('is_active', true)->get(),
            'days' => self::DAYS,
            'levelId' => $levelId,
            'academicYearId' => $academicYearId,
            'semesterId' => $semesterId,
            'weeks' => $weeks,
        ]);
    }

    public function generateWeeks(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'level_id' => ['required', 'exists:levels,id'],
            'academic_year_id' => ['required', 'exists:academic_years,id'],
            'semester_id' => ['required', 'exists:semesters,id'],
            'week_count' => ['required', 'integer', 'min:1', 'max:52'],
        ]);

        for ($week = 1; $week <= $validated['week_count']; $week++) {
            DefaultSchedule::firstOrCreate([
                'level_id' => $validated['level_id'],
                'academic_year_id' => $validated['academic_year_id'],
                'semester_id' => $validated['semester_id'],
                'week_number' => $week,
            ]);
        }

        return redirect()->route('default-schedules.index', $request->only(['level_id', 'academic_year_id', 'semester_id']))
            ->with('success', "Minggu 1–{$validated['week_count']} berhasil dibuat.");
    }

    public function saveBatch(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'level_id' => ['required', 'exists:levels,id'],
            'academic_year_id' => ['required', 'exists:academic_years,id'],
            'semester_id' => ['required', 'exists:semesters,id'],
            'weeks' => ['nullable', 'array'],
            'weeks.*.scheduled_date' => ['nullable', 'date'],
            'weeks.*.day_of_week' => ['nullable', 'integer', 'min:1', 'max:7'],
            'weeks.*.start_time' => ['nullable', 'date_format:H:i'],
            'weeks.*.end_time' => ['nullable', 'date_format:H:i'],
        ]);

        foreach ($validated['weeks'] ?? [] as $id => $entry) {
            DefaultSchedule::where('level_id', $validated['level_id'])
                ->where('academic_year_id', $validated['academic_year_id'])
                ->where('semester_id', $validated['semester_id'])
                ->whereKey($id)
                ->update($entry);
        }

        return redirect()->route('default-schedules.index', $request->only(['level_id', 'academic_year_id', 'semester_id']))
            ->with('success', 'Jadwal KBM Default berhasil disimpan.');
    }
}
