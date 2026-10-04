<?php

namespace App\Http\Controllers;

use App\Exports\LearningMaterialExport;
use App\Imports\LearningMaterialImport;
use App\Models\AcademicYear;
use App\Models\LearningMaterial;
use App\Models\MaterialChapter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class LearningMaterialController extends Controller
{
    public function index(): View
    {
        return view('learning-materials.index', [
            'materials' => LearningMaterial::with(['materialChapter.materialCategory.classGrade', 'materialChapter.materialCategory.semester', 'academicYear'])
                ->orderBy('material_chapter_id')
                ->orderBy('sort_order')
                ->paginate(25),
            'chapters' => MaterialChapter::with(['materialCategory.classGrade', 'materialCategory.semester'])
                ->where('is_active', true)
                ->orderBy('material_category_id')
                ->orderBy('sort_order')
                ->get(),
            'academicYears' => AcademicYear::where('is_active', true)->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'material_chapter_id' => ['required', 'exists:material_chapters,id'],
            'title' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:50', 'unique:learning_materials,code'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'academic_year_id' => ['nullable', 'exists:academic_years,id'],
            'description' => ['nullable', 'string'],
            'is_active' => ['required', 'boolean'],
        ]);

        $validated['sort_order'] ??= 0;

        LearningMaterial::create($validated);

        return redirect()->route('learning-materials.index')->with('success', 'Materi pembelajaran berhasil ditambahkan.');
    }

    public function import(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'file' => ['required', 'file', 'mimes:xlsx', 'max:10240'],
        ]);

        DB::transaction(function () use ($validated): void {
            $import = new LearningMaterialImport;

            Excel::import($import, $validated['file']);

            if ($import->errors() !== []) {
                throw ValidationException::withMessages($import->errors());
            }
        });

        return redirect()->route('learning-materials.index')->with('success', 'Materi pembelajaran berhasil diimpor dari XLSX.');
    }

    public function export(Request $request): BinaryFileResponse
    {
        return Excel::download(new LearningMaterialExport, 'materi-pembelajaran.xlsx');
    }
}
