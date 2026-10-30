<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\HandlesSheets;
use App\Models\MaterialCategory;
use App\Models\MaterialChapter;
use App\Support\Sheets\MaterialChapterSheet;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class MaterialChapterController extends Controller
{
    use HandlesSheets;

    public function index(Request $request): View
    {
        $search = trim((string) $request->query('q', ''));
        $categoryId = $request->query('material_category_id', '');

        $chapters = MaterialChapter::with(['materialCategory.classGrade', 'materialCategory.semester'])
            ->when($search !== '', function ($query) use ($search): void {
                $like = '%'.addcslashes($search, '%_\\').'%';
                $query->where(fn ($query) => $query->where('name', 'like', $like)->orWhere('code', 'like', $like));
            })
            ->when($categoryId !== '', fn ($query) => $query->where('material_category_id', $categoryId))
            ->orderBy('material_category_id')
            ->orderBy('sort_order')
            ->paginate(25)
            ->withQueryString();

        return view('material-chapters.index', [
            'chapters' => $chapters,
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

    public function export(): BinaryFileResponse
    {
        return $this->exportSheet(new MaterialChapterSheet);
    }

    public function import(Request $request): RedirectResponse
    {
        return $this->importSheet($request, new MaterialChapterSheet);
    }
}
