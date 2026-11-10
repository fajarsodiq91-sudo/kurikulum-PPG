<?php

namespace App\Http\Controllers;

use App\Models\AcademicYear;
use App\Models\ClassGrade;
use App\Models\MunaqosahSchedule;
use App\Models\Semester;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * PPG Daerah-only: opening a kelas KBM's munaqosah window for a semester (when it runs, what
 * materi is tested) and closing it again once done. Pelaksana Kelompok can only record
 * munaqosah type=semester-final scores while the matching schedule is_open.
 */
class MunaqosahScheduleController extends Controller
{
    public function index(Request $request): View
    {
        $academicYearId = $request->integer('academic_year_id') ?: null;
        $semesterId = $request->integer('semester_id') ?: null;

        $schedules = ($academicYearId && $semesterId)
            ? MunaqosahSchedule::with('classGrade.level')
                ->where('academic_year_id', $academicYearId)
                ->where('semester_id', $semesterId)
                ->get()
                ->sortBy(fn (MunaqosahSchedule $schedule): string => sprintf('%05d-%05d', $schedule->classGrade->level->sort_order, $schedule->classGrade->sort_order))
                ->values()
            : collect();

        return view('munaqosah-schedules.index', [
            'classGrades' => ClassGrade::where('is_active', true)->with('level')->get()
                ->sortBy(fn (ClassGrade $grade): string => sprintf('%05d-%05d', $grade->level->sort_order, $grade->sort_order))
                ->values(),
            'academicYears' => AcademicYear::where('is_active', true)->get(),
            'semesters' => Semester::where('is_active', true)->get(),
            'academicYearId' => $academicYearId,
            'semesterId' => $semesterId,
            'schedules' => $schedules->keyBy('class_grade_id'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'class_grade_id' => ['required', 'exists:class_grades,id'],
            'academic_year_id' => ['required', 'exists:academic_years,id'],
            'semester_id' => ['required', 'exists:semesters,id'],
            'type' => ['required', 'string', 'max:50'],
            'materials' => ['nullable', 'string'],
            'scheduled_start' => ['nullable', 'date'],
            'scheduled_end' => ['nullable', 'date', 'after_or_equal:scheduled_start'],
            'is_open' => ['nullable', 'boolean'],
        ]);

        MunaqosahSchedule::updateOrCreate(
            [
                'class_grade_id' => $validated['class_grade_id'],
                'academic_year_id' => $validated['academic_year_id'],
                'semester_id' => $validated['semester_id'],
                'type' => $validated['type'],
            ],
            [
                'materials' => $validated['materials'] ?? null,
                'scheduled_start' => $validated['scheduled_start'] ?? null,
                'scheduled_end' => $validated['scheduled_end'] ?? null,
                'is_open' => $request->boolean('is_open'),
                'opened_by_id' => $request->boolean('is_open') ? $request->user()->id : null,
            ],
        );

        return redirect()->route('munaqosah-schedules.index', $request->only(['academic_year_id', 'semester_id']))
            ->with('success', 'Jadwal munaqosah berhasil disimpan.');
    }

    public function destroy(MunaqosahSchedule $munaqosahSchedule): RedirectResponse
    {
        $munaqosahSchedule->delete();

        return redirect()->route('munaqosah-schedules.index', [
            'academic_year_id' => $munaqosahSchedule->academic_year_id,
            'semester_id' => $munaqosahSchedule->semester_id,
        ])->with('success', 'Jadwal munaqosah berhasil dihapus.');
    }
}
