<?php

namespace App\Http\Controllers;

use App\Models\ClassGrade;
use App\Models\MaterialCategory;
use App\Models\Semester;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class MaterialCategoryController extends Controller
{
    public function index(): View
    {
        return view('material-categories.index', [
            'categories' => MaterialCategory::with(['classGrade', 'semester'])
                ->orderBy('class_grade_id')
                ->orderBy('semester_id')
                ->orderBy('sort_order')
                ->paginate(25),
            'classGrades' => ClassGrade::where('is_active', true)->orderBy('sort_order')->get(),
            'semesters' => Semester::where('is_active', true)->orderBy('sort_order')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'class_grade_id' => ['required', 'exists:class_grades,id'],
            'semester_id' => ['required', 'exists:semesters,id'],
            'name' => ['required', 'string', 'max:255'],
            'code' => [
                'required',
                'string',
                'max:50',
                Rule::unique('material_categories', 'code')
                    ->where('class_grade_id', $request->integer('class_grade_id'))
                    ->where('semester_id', $request->integer('semester_id')),
            ],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'description' => ['nullable', 'string'],
            'is_active' => ['required', 'boolean'],
        ]);

        $validated['sort_order'] ??= 0;

        MaterialCategory::create($validated);

        return redirect()->route('material-categories.index')->with('success', 'Kategori materi berhasil ditambahkan.');
    }
}
