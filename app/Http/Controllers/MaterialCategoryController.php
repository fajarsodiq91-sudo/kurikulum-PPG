<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\HandlesSheets;
use App\Models\ClassGrade;
use App\Models\MaterialCategory;
use App\Models\Semester;
use App\Support\Sheets\MaterialCategorySheet;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class MaterialCategoryController extends Controller
{
    use HandlesSheets;

    public function index(Request $request): View
    {
        $search = trim((string) $request->query('q', ''));
        $classGradeId = $request->query('class_grade_id', '');
        $semesterId = $request->query('semester_id', '');

        $categories = MaterialCategory::with(['classGrade', 'semester'])
            ->when($search !== '', function ($query) use ($search): void {
                $like = '%'.addcslashes($search, '%_\\').'%';
                $query->where(fn ($query) => $query->where('name', 'like', $like)->orWhere('code', 'like', $like));
            })
            ->when($classGradeId !== '', fn ($query) => $query->where('class_grade_id', $classGradeId))
            ->when($semesterId !== '', fn ($query) => $query->where('semester_id', $semesterId))
            ->orderBy('class_grade_id')
            ->orderBy('semester_id')
            ->orderBy('sort_order')
            ->paginate(25)
            ->withQueryString();

        return view('material-categories.index', [
            'categories' => $categories,
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

    public function update(Request $request, MaterialCategory $material_category): RedirectResponse
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
                    ->where('semester_id', $request->integer('semester_id'))
                    ->ignore($material_category->id),
            ],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'description' => ['nullable', 'string'],
            'is_active' => ['required', 'boolean'],
        ]);

        $validated['sort_order'] ??= 0;

        $material_category->update($validated);

        return redirect()->route('material-categories.index')->with('success', 'Kategori materi berhasil diperbarui.');
    }

    public function destroy(MaterialCategory $material_category): RedirectResponse
    {
        try {
            $material_category->delete();
        } catch (QueryException $e) {
            if ($e->getCode() === '23000') {
                return redirect()->route('material-categories.index')->withErrors([
                    'delete' => 'Kategori materi tidak dapat dihapus karena masih memiliki bab materi.',
                ]);
            }

            throw $e;
        }

        return redirect()->route('material-categories.index')->with('success', 'Kategori materi berhasil dihapus.');
    }

    public function export(): BinaryFileResponse
    {
        return $this->exportSheet(new MaterialCategorySheet);
    }

    public function import(Request $request): RedirectResponse
    {
        return $this->importSheet($request, new MaterialCategorySheet);
    }
}
