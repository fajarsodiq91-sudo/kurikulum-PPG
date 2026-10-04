<?php

namespace App\Http\Controllers;

use App\Models\MaterialCategory;
use App\Models\MaterialChapter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class MaterialChapterController extends Controller
{
    public function index(): View
    {
        return view('material-chapters.index', [
            'chapters' => MaterialChapter::with(['materialCategory.classGrade', 'materialCategory.semester'])
                ->orderBy('material_category_id')
                ->orderBy('sort_order')
                ->paginate(25),
            'categories' => MaterialCategory::with(['classGrade', 'semester'])
                ->where('is_active', true)
                ->orderBy('class_grade_id')
                ->orderBy('semester_id')
                ->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'material_category_id' => ['required', 'exists:material_categories,id'],
            'name' => ['required', 'string', 'max:255'],
            'code' => [
                'required',
                'string',
                'max:50',
                Rule::unique('material_chapters', 'code')->where('material_category_id', $request->integer('material_category_id')),
            ],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'description' => ['nullable', 'string'],
            'is_active' => ['required', 'boolean'],
        ]);

        $validated['sort_order'] ??= 0;

        MaterialChapter::create($validated);

        return redirect()->route('material-chapters.index')->with('success', 'Bab materi berhasil ditambahkan.');
    }
}
