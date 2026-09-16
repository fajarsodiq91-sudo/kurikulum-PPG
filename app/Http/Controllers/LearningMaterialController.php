<?php

namespace App\Http\Controllers;

use App\Models\AcademicYear;
use App\Models\CurriculumProgram;
use App\Models\LearningMaterial;
use App\Models\Level;
use App\Models\Semester;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LearningMaterialController extends Controller
{
    public function index(): View
    {
        return view('learning-materials.index', [
            'materials' => LearningMaterial::with(['curriculumProgram', 'level', 'semester', 'academicYear'])->latest()->get(),
            'programs' => CurriculumProgram::where('is_active', true)->get(),
            'levels' => Level::where('is_active', true)->orderBy('sort_order')->get(),
            'semesters' => Semester::where('is_active', true)->get(),
            'academicYears' => AcademicYear::where('is_active', true)->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'curriculum_program_id' => ['required', 'exists:curriculum_programs,id'],
            'title' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:50', 'unique:learning_materials,code'],
            'level_id' => ['required', 'exists:levels,id'],
            'semester_id' => ['required', 'exists:semesters,id'],
            'academic_year_id' => ['required', 'exists:academic_years,id'],
            'description' => ['nullable', 'string'],
            'is_active' => ['required', 'boolean'],
        ]);

        LearningMaterial::create($validated);

        return redirect()->route('learning-materials.index')->with('success', 'Materi pembelajaran berhasil ditambahkan.');
    }
}
