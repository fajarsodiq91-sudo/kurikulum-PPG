<?php

namespace App\Http\Controllers;

use App\Models\AcademicYear;
use App\Models\ClassGrade;
use App\Models\Generus;
use App\Models\GradeScale;
use App\Models\Group;
use App\Models\LearningMaterial;
use App\Models\MaterialCategory;
use App\Models\Semester;
use App\Models\Ujian;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Pencatatan pencapaian materi RPP per kategori target generus (bacaan, hafalan, menulis,
 * praktik ibadah, akhlaq, kemandirian). Setiap kategori memakai halaman yang sama, dibedakan
 * oleh parameter route {category} yang dicocokkan dengan kode `material_categories.code` pada
 * kelas KBM & semester generus yang bersangkutan. Nilainya disimpan sebagai baris `Ujian`
 * biasa (bertaut ke materi lewat `material_id`) supaya otomatis ikut dirata-rata ke rapor oleh
 * App\Support\ReportCardScores.
 */
class KontrolController extends Controller
{
    /**
     * @var array<string, string>
     */
    public const CATEGORIES = [
        'bacaan' => 'Bacaan',
        'hafalan' => 'Hafalan',
        'menulis' => 'Menulis',
        'praktik-ibadah' => 'Praktik Ibadah',
        'akhlaq' => 'Akhlaq',
        'kemandirian' => 'Kemandirian',
    ];

    public function batch(Request $request, string $category): View
    {
        $label = $this->label($category);
        $user = $request->user();

        $groups = Group::where('is_active', true)->manageableBy($user, Ujian::MANAGE_PERMISSION)->with('village')->orderBy('name')->get();
        $classGrades = ClassGrade::where('is_active', true)->orderBy('sort_order')->get();

        $group = $request->filled('group_id')
            ? $groups->firstWhere('id', $request->integer('group_id'))
            : null;

        $classGrade = $request->filled('class_grade_id')
            ? $classGrades->firstWhere('id', $request->integer('class_grade_id'))
            : null;

        $academicYearId = $request->integer('academic_year_id') ?: null;
        $semesterId = $request->integer('semester_id') ?: null;
        $type = $request->string('type')->value() ?: 'harian';
        $materialId = $request->integer('material_id') ?: null;

        $roster = $this->rosterFor($user, $group, $classGrade);

        $materialCategory = ($classGrade && $semesterId)
            ? MaterialCategory::where('class_grade_id', $classGrade->id)
                ->where('semester_id', $semesterId)
                ->where('code', $category)
                ->with('materialChapters.learningMaterials')
                ->first()
            : null;

        $materials = $materialCategory
            ? $materialCategory->materialChapters
                ->flatMap(fn ($chapter) => $chapter->learningMaterials->where('is_active', true))
                ->values()
            : collect();

        $existing = ($academicYearId && $semesterId && $materialId)
            ? Ujian::where('academic_year_id', $academicYearId)
                ->where('semester_id', $semesterId)
                ->where('type', $type)
                ->where('material_id', $materialId)
                ->whereIn('generus_id', $roster->pluck('id'))
                ->get()
                ->keyBy('generus_id')
            : collect();

        return view('kontrol.batch', [
            'category' => $category,
            'label' => $label,
            'groups' => $groups,
            'classGrades' => $classGrades,
            'group' => $group,
            'classGrade' => $classGrade,
            'roster' => $roster,
            'materialCategory' => $materialCategory,
            'materials' => $materials,
            'existing' => $existing,
            'academicYearId' => $academicYearId,
            'semesterId' => $semesterId,
            'type' => $type,
            'materialId' => $materialId,
            'academicYears' => AcademicYear::where('is_active', true)->get(),
            'semesters' => Semester::where('is_active', true)->get(),
        ]);
    }

    public function saveBatch(Request $request, string $category): RedirectResponse
    {
        $this->label($category);
        $user = $request->user();

        $manageableGroupIds = Group::where('is_active', true)->manageableBy($user, Ujian::MANAGE_PERMISSION)->pluck('id')->all();

        $validated = $request->validate([
            'group_id' => ['required', Rule::in($manageableGroupIds)],
            'class_grade_id' => ['required', 'exists:class_grades,id'],
            'academic_year_id' => ['required', 'exists:academic_years,id'],
            'semester_id' => ['required', 'exists:semesters,id'],
            'type' => ['required', 'string', 'max:50'],
            'material_id' => ['required', 'exists:learning_materials,id'],
            'scores' => ['nullable', 'array'],
            'scores.*.score' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'scores.*.result' => ['nullable', 'string', 'max:50'],
            'scores.*.status' => ['required', 'string', 'max:50'],
            'scores.*.notes' => ['nullable', 'string'],
        ]);

        $group = Group::findOrFail($validated['group_id']);
        $classGrade = ClassGrade::findOrFail($validated['class_grade_id']);

        $material = LearningMaterial::with('materialChapter.materialCategory')->findOrFail($validated['material_id']);
        $materialCategory = $material->materialChapter->materialCategory;

        abort_unless(
            $materialCategory->class_grade_id === $classGrade->id
                && $materialCategory->semester_id === (int) $validated['semester_id']
                && $materialCategory->code === $category,
            422,
            'Materi tidak sesuai dengan kategori, kelas, atau semester yang dipilih.'
        );

        $rosterIds = $this->rosterFor($user, $group, $classGrade)->pluck('id')->map(fn (mixed $id): int => (int) $id)->all();

        foreach ($validated['scores'] ?? [] as $generusId => $entry) {
            abort_unless(in_array((int) $generusId, $rosterIds, true), 422, 'Generus tidak termasuk dalam kelompok/kelas KBM yang dipilih.');

            if (blank($entry['score'] ?? null) && blank($entry['result'] ?? null)) {
                continue;
            }

            $data = $this->applyGradeScale([
                'title' => $material->title,
                'material_id' => $material->id,
                'type' => $validated['type'],
                'score' => $entry['score'] ?? null,
                'result' => $entry['result'] ?? null,
                'status' => $entry['status'],
                'notes' => $entry['notes'] ?? null,
            ]);

            Ujian::updateOrCreate(
                [
                    'generus_id' => (int) $generusId,
                    'academic_year_id' => $validated['academic_year_id'],
                    'semester_id' => $validated['semester_id'],
                    'type' => $validated['type'],
                    'material_id' => $material->id,
                ],
                $data,
            );
        }

        return redirect()->route("kontrol.{$category}", $request->only(['group_id', 'class_grade_id', 'academic_year_id', 'semester_id', 'type', 'material_id']))
            ->with('success', "Nilai kontrol {$this->label($category)} berhasil disimpan.");
    }

    private function label(string $category): string
    {
        abort_unless(array_key_exists($category, self::CATEGORIES), 404);

        return self::CATEGORIES[$category];
    }

    /**
     * Roster kelompok, disaring ke generus yang kelas KBM-nya sama dengan kelas yang dipilih —
     * ini yang membuat materi yang tampil otomatis mengikuti kelas KBM generus.
     *
     * @return Collection<int, Generus>
     */
    private function rosterFor(?User $user, ?Group $group, ?ClassGrade $classGrade): Collection
    {
        if (! $group || ! $classGrade) {
            return collect();
        }

        return $group->rosterFor($user, Ujian::MANAGE_PERMISSION)
            ->where('learning_class_id', $classGrade->id)
            ->get();
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
}
