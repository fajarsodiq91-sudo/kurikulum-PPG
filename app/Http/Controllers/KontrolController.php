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
use App\Support\SemesterResolver;
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
        $teacher = $user?->teacher;

        $villages = $groups->pluck('village')->filter()->unique('id')->sortBy('name')->values();

        $defaultGroup = ($teacher?->group_id !== null ? $groups->firstWhere('id', $teacher->group_id) : null)
            ?? ($groups->count() === 1 ? $groups->first() : null);

        $villageId = $request->integer('village_id') ?: $defaultGroup?->village_id;

        $group = $request->filled('group_id')
            ? $groups->firstWhere('id', $request->integer('group_id'))
            : $defaultGroup;

        $groupIds = $this->resolveGroupIds($groups, $group, $villageId);

        $taughtClassGradeIds = $teacher ? $teacher->classGrades()->pluck('class_grades.id') : collect();
        $classGradeOptions = $taughtClassGradeIds->isNotEmpty()
            ? $classGrades->whereIn('id', $taughtClassGradeIds)->values()
            : $classGrades;

        $classGrade = $request->filled('class_grade_id')
            ? $classGrades->firstWhere('id', $request->integer('class_grade_id'))
            : ($classGradeOptions->count() === 1 ? $classGradeOptions->first() : null);

        $currentSemester = SemesterResolver::resolveFor(now());

        $academicYearId = $request->integer('academic_year_id') ?: $currentSemester?->academic_year_id;
        $semesterId = $request->integer('semester_id') ?: $currentSemester?->id;
        $type = 'harian';
        $materialIds = $this->integerList($request->input('material_id', []));

        $roster = $this->rosterFor($user, $groupIds, $classGrade);

        $materialCategory = ($classGrade && $semesterId)
            ? MaterialCategory::where('class_grade_id', $classGrade->id)
                ->where('semester_id', $semesterId)
                ->where('code', $category)
                ->with(['materialChapters' => fn ($query) => $query->where('is_active', true)->orderBy('sort_order')])
                ->with(['materialChapters.learningMaterials' => fn ($query) => $query->where('is_active', true)->orderBy('sort_order')])
                ->first()
            : null;

        $materials = $materialCategory
            ? $materialCategory->materialChapters
                ->flatMap(fn ($chapter) => $chapter->learningMaterials)
                ->values()
            : collect();

        $selectedMaterials = $materialIds !== [] ? $materials->whereIn('id', $materialIds)->values() : collect();

        $existing = ($academicYearId && $semesterId && $selectedMaterials->isNotEmpty())
            ? Ujian::where('academic_year_id', $academicYearId)
                ->where('semester_id', $semesterId)
                ->where('type', $type)
                ->whereIn('material_id', $selectedMaterials->pluck('id'))
                ->whereIn('generus_id', $roster->pluck('id'))
                ->get()
                ->groupBy('material_id')
                ->map(fn (Collection $scores) => $scores->keyBy('generus_id'))
            : collect();

        return view('kontrol.batch', [
            'category' => $category,
            'label' => $label,
            'villages' => $villages,
            'villageId' => $villageId,
            'groups' => $groups,
            'classGrades' => $classGradeOptions,
            'group' => $group,
            'groupIds' => $groupIds,
            'classGrade' => $classGrade,
            'roster' => $roster,
            'materialCategory' => $materialCategory,
            'materials' => $materials,
            'materialIds' => $materialIds,
            'selectedMaterials' => $selectedMaterials,
            'existing' => $existing,
            'academicYearId' => $academicYearId,
            'semesterId' => $semesterId,
            'type' => $type,
            'academicYears' => AcademicYear::where('is_active', true)->get(),
            'semesters' => Semester::where('is_active', true)->get(),
        ]);
    }

    public function saveBatch(Request $request, string $category): RedirectResponse
    {
        $this->label($category);
        $user = $request->user();

        $groups = Group::where('is_active', true)->manageableBy($user, Ujian::MANAGE_PERMISSION)->get();
        $manageableGroupIds = $groups->pluck('id')->all();

        $validated = $request->validate([
            'group_id' => ['nullable', Rule::in($manageableGroupIds)],
            'village_id' => ['nullable', 'integer'],
            'class_grade_id' => ['required', 'exists:class_grades,id'],
            'academic_year_id' => ['required', 'exists:academic_years,id'],
            'semester_id' => ['required', 'exists:semesters,id'],
            'type' => ['required', 'string', 'max:50'],
            'material_id' => ['required', 'array', 'min:1'],
            'material_id.*' => ['distinct', 'exists:learning_materials,id'],
            'scores' => ['nullable', 'array'],
            'scores.*' => ['array'],
            'scores.*.*.score' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'scores.*.*.result' => ['nullable', 'string', 'max:50'],
            'scores.*.*.status' => ['required', 'string', 'max:50'],
            'scores.*.*.notes' => ['nullable', 'string'],
        ]);

        $group = filled($validated['group_id'] ?? null) ? Group::findOrFail($validated['group_id']) : null;
        $groupIds = $this->resolveGroupIds($groups, $group, $validated['village_id'] ?? null);
        $classGrade = ClassGrade::findOrFail($validated['class_grade_id']);

        $rosterIds = $this->rosterFor($user, $groupIds, $classGrade)->pluck('id')->map(fn (mixed $id): int => (int) $id)->all();

        foreach ($validated['material_id'] as $materialId) {
            $material = LearningMaterial::with('materialChapter.materialCategory')->findOrFail($materialId);
            $materialCategory = $material->materialChapter->materialCategory;

            abort_unless(
                $materialCategory->class_grade_id === $classGrade->id
                    && $materialCategory->semester_id === (int) $validated['semester_id']
                    && $materialCategory->code === $category,
                422,
                'Materi tidak sesuai dengan kategori, kelas, atau semester yang dipilih.'
            );

            foreach ($validated['scores'][$materialId] ?? [] as $generusId => $entry) {
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
        }

        return redirect()->route("kontrol.{$category}", $request->only(['group_id', 'village_id', 'class_grade_id', 'academic_year_id', 'semester_id', 'type', 'material_id']))
            ->with('success', "Nilai kontrol {$this->label($category)} berhasil disimpan.");
    }

    private function label(string $category): string
    {
        abort_unless(array_key_exists($category, self::CATEGORIES), 404);

        return self::CATEGORIES[$category];
    }

    /**
     * Roster kelompok (satu atau beberapa sekaligus, saat tidak ada kelompok spesifik dipilih),
     * disaring ke generus yang kelas KBM-nya sama dengan kelas yang dipilih — ini yang membuat
     * materi yang tampil otomatis mengikuti kelas KBM generus.
     *
     * @param  list<int>  $groupIds
     * @return Collection<int, Generus>
     */
    private function rosterFor(?User $user, array $groupIds, ?ClassGrade $classGrade): Collection
    {
        if ($groupIds === [] || ! $classGrade) {
            return collect();
        }

        return Group::rosterForGroups($groupIds, $user, Ujian::MANAGE_PERMISSION)
            ->where('learning_class_id', $classGrade->id)
            ->get();
    }

    /**
     * Kelompok mana saja yang dicakup halaman ini: satu kelompok spesifik kalau dipilih,
     * atau seluruh kelompok yang bisa dikelola user (dipersempit ke satu desa bila dipilih).
     *
     * @param  Collection<int, Group>  $groups
     * @return list<int>
     */
    private function resolveGroupIds(Collection $groups, ?Group $group, ?int $villageId): array
    {
        if ($group) {
            return [$group->id];
        }

        $scoped = $villageId ? $groups->where('village_id', $villageId) : $groups;

        return $scoped->pluck('id')->map(fn (mixed $id): int => (int) $id)->values()->all();
    }

    /**
     * Normalizes a request array of raw values (e.g. `material_id[]`) into distinct positive ints.
     *
     * @return list<int>
     */
    private function integerList(mixed $values): array
    {
        return collect(is_array($values) ? $values : [])
            ->map(fn (mixed $value): int => (int) $value)
            ->filter(fn (int $value): bool => $value > 0)
            ->unique()
            ->values()
            ->all();
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
