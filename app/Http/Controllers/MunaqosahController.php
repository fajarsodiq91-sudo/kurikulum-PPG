<?php

namespace App\Http\Controllers;

use App\Models\AcademicYear;
use App\Models\Generus;
use App\Models\GradeScale;
use App\Models\Munaqosah;
use App\Models\Semester;
use App\Support\StudentScope;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MunaqosahController extends Controller
{
    public function index(): View
    {
        return view('munaqosahs.index', [
            'munaqosahs' => StudentScope::limit(Munaqosah::with(['generus', 'academicYear', 'semester'])->latest(), $this->studentIds())->paginate(25),
            'generus' => StudentScope::limit(Generus::query(), $this->studentIds(), 'id')->get(),
            'academicYears' => AcademicYear::where('is_active', true)->get(),
            'semesters' => Semester::where('is_active', true)->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'generus_id' => ['required', StudentScope::existsRule($request->user(), 'manage-munaqosah')],
            'academic_year_id' => ['required', 'exists:academic_years,id'],
            'semester_id' => ['required', 'exists:semesters,id'],
            'title' => ['required', 'string', 'max:255'],
            'type' => ['required', 'string', 'max:50'],
            'score' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'result' => ['nullable', 'string', 'max:50'],
            'status' => ['required', 'string', 'max:50'],
            'notes' => ['nullable', 'string'],
        ]);

        Munaqosah::create($this->applyGradeScale($validated));

        return redirect()->route('munaqosahs.index')->with('success', 'Munaqosah berhasil ditambahkan.');
    }

    /**
     * Fills in the letter grade and its description from the score when the result was left blank.
     *
     * @param  array<string, mixed>  $validated
     * @return array<string, mixed>
     */
    private function applyGradeScale(array $validated): array
    {
        if (blank($validated['result'] ?? null) && filled($validated['score'] ?? null)) {
            $scale = GradeScale::resolve((float) $validated['score']);

            if ($scale) {
                $validated['result'] = $scale->grade;
                $validated['grade_scale_id'] = $scale->id;
                $validated['grade_description'] = $scale->description;
            }
        }

        return $validated;
    }

    private function studentIds(): ?array
    {
        return StudentScope::ids(request()->user(), 'manage-munaqosah');
    }
}
