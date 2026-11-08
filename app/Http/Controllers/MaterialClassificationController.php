<?php

namespace App\Http\Controllers;

use App\Models\LearningMaterial;
use App\Models\MaterialChapter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Halaman untuk mengklasifikasikan setiap materi ke dalam jenis target generus (bacaan,
 * hafalan, menulis, praktik ibadah, akhlaq, kemandirian) yang sama dengan daftar kategori di
 * App\Http\Controllers\KontrolController::CATEGORIES.
 */
class MaterialClassificationController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('q', ''));
        $chapterId = $request->query('material_chapter_id', '');
        $jenis = $request->query('jenis', '');

        $materials = LearningMaterial::with(['materialChapter.materialCategory.classGrade', 'materialChapter.materialCategory.semester'])
            ->when($search !== '', function ($query) use ($search): void {
                $like = '%'.addcslashes($search, '%_\\').'%';
                $query->where(fn ($query) => $query->where('title', 'like', $like)->orWhere('code', 'like', $like));
            })
            ->when($chapterId !== '', fn ($query) => $query->where('material_chapter_id', $chapterId))
            ->when($jenis !== '', fn ($query) => $query->where('jenis', $jenis))
            ->orderBy('material_chapter_id')
            ->orderBy('sort_order')
            ->paginate(25)
            ->withQueryString();

        return view('material-classifications.index', [
            'materials' => $materials,
            'chapters' => MaterialChapter::with(['materialCategory.classGrade', 'materialCategory.semester'])
                ->where('is_active', true)
                ->orderBy('material_category_id')
                ->orderBy('sort_order')
                ->get(),
            'categories' => KontrolController::CATEGORIES,
        ]);
    }

    public function update(Request $request, LearningMaterial $learning_material): RedirectResponse
    {
        $validated = $request->validate([
            'jenis' => ['nullable', 'string', Rule::in(array_keys(KontrolController::CATEGORIES))],
        ]);

        $learning_material->update($validated);

        return redirect()->route('material-classifications.index', $request->query())->with('success', 'Klasifikasi materi "'.$learning_material->title.'" berhasil disimpan.');
    }
}
