<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\HandlesSheets;
use App\Models\AcademicYear;
use App\Models\Evaluation;
use App\Models\LearningMaterial;
use App\Models\Semester;
use App\Models\Teacher;
use App\Support\Sheets\EvaluationSheet;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class EvaluationController extends Controller
{
    use HandlesSheets;

    public function index(): View
    {
        return view('evaluations.index', [
            'evaluations' => Evaluation::with(['teacher', 'material', 'semester', 'academicYear'])->latest()->paginate(25),
            'teachers' => Teacher::where('status', 'active')->get(),
            'materials' => LearningMaterial::where('is_active', true)->get(),
            'semesters' => Semester::where('is_active', true)->get(),
            'academicYears' => AcademicYear::where('is_active', true)->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'teacher_id' => ['required', 'exists:teachers,id'],
            'material_id' => ['required', 'exists:learning_materials,id'],
            'semester_id' => ['required', 'exists:semesters,id'],
            'academic_year_id' => ['required', 'exists:academic_years,id'],
            'title' => ['required', 'string', 'max:255'],
            'type' => ['required', 'string', 'max:50'],
            'scheduled_at' => ['nullable', 'date'],
            'status' => ['required', 'string', 'max:50'],
            'notes' => ['nullable', 'string'],
        ]);

        Evaluation::create($validated);

        return redirect()->route('evaluations.index')->with('success', 'Evaluasi berhasil ditambahkan.');
    }

    public function export(): BinaryFileResponse
    {
        return $this->exportSheet(new EvaluationSheet);
    }

    public function import(Request $request): RedirectResponse
    {
        return $this->importSheet($request, new EvaluationSheet);
    }
}
