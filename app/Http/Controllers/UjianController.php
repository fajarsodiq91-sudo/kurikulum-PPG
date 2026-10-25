<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\HandlesSheets;
use App\Models\AcademicYear;
use App\Models\Generus;
use App\Models\GradeScale;
use App\Models\Group;
use App\Models\Semester;
use App\Models\Ujian;
use App\Support\Sheets\UjianSheet;
use App\Support\StudentScope;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class UjianController extends Controller
{
    use HandlesSheets;

    public function index(): View
    {
        return view('ujians.index', [
            'ujians' => StudentScope::limit(Ujian::with(['generus', 'academicYear', 'semester'])->latest(), StudentScope::viewIds(request()->user(), Ujian::MANAGE_PERMISSION))->paginate(25),
            ...$this->formOptions(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        Ujian::create($this->applyGradeScale($this->validateUjian($request)));

        return redirect()->route('ujians.index')->with('success', 'Ujian berhasil ditambahkan.');
    }

    public function edit(Ujian $ujian): View
    {
        $this->authorizeRecord($ujian);

        return view('ujians.edit', [
            'ujian' => $ujian,
            ...$this->formOptions(),
        ]);
    }

    public function update(Request $request, Ujian $ujian): RedirectResponse
    {
        $this->authorizeRecord($ujian);

        $ujian->update($this->applyGradeScale($this->validateUjian($request, $ujian)));

        return redirect()->route('ujians.index')->with('success', 'Ujian berhasil diperbarui.');
    }

    public function destroy(Ujian $ujian): RedirectResponse
    {
        $this->authorizeRecord($ujian);

        $ujian->delete();

        return redirect()->route('ujians.index')->with('success', 'Ujian berhasil dihapus.');
    }

    /**
     * One kelompok's ujian sheet for a given year, semester, and type: every generus in the
     * group with a score input, prefilled when already recorded.
     */
    public function batch(Request $request): View
    {
        $user = $request->user();

        $groups = Group::where('is_active', true)->manageableBy($user, Ujian::MANAGE_PERMISSION)->with('village')->orderBy('name')->get();

        $group = $request->filled('group_id')
            ? $groups->firstWhere('id', $request->integer('group_id'))
            : null;

        $academicYearId = $request->integer('academic_year_id') ?: null;
        $semesterId = $request->integer('semester_id') ?: null;
        $type = $request->string('type')->value() ?: 'harian';

        $roster = $group?->rosterFor($user, Ujian::MANAGE_PERMISSION)->get() ?? collect();

        $existing = $academicYearId && $semesterId
            ? Ujian::where('academic_year_id', $academicYearId)
                ->where('semester_id', $semesterId)
                ->where('type', $type)
                ->whereIn('generus_id', $roster->pluck('id'))
                ->get()
                ->keyBy('generus_id')
            : collect();

        return view('ujians.batch', [
            'groups' => $groups,
            'group' => $group,
            'roster' => $roster,
            'existing' => $existing,
            'academicYearId' => $academicYearId,
            'semesterId' => $semesterId,
            'type' => $type,
            'academicYears' => AcademicYear::where('is_active', true)->get(),
            'semesters' => Semester::where('is_active', true)->get(),
        ]);
    }

    public function saveBatch(Request $request): RedirectResponse
    {
        $user = $request->user();

        $manageableGroupIds = Group::where('is_active', true)->manageableBy($user, Ujian::MANAGE_PERMISSION)->pluck('id')->all();

        $validated = $request->validate([
            'group_id' => ['required', Rule::in($manageableGroupIds)],
            'academic_year_id' => ['required', 'exists:academic_years,id'],
            'semester_id' => ['required', 'exists:semesters,id'],
            'type' => ['required', 'string', 'max:50'],
            'title' => ['required', 'string', 'max:255'],
            'scores' => ['nullable', 'array'],
            'scores.*.score' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'scores.*.result' => ['nullable', 'string', 'max:50'],
            'scores.*.status' => ['required', 'string', 'max:50'],
            'scores.*.notes' => ['nullable', 'string'],
        ]);

        $group = Group::findOrFail($validated['group_id']);
        $rosterIds = $group->rosterFor($user, Ujian::MANAGE_PERMISSION)->pluck('id')->map(fn (mixed $id): int => (int) $id)->all();

        foreach ($validated['scores'] ?? [] as $generusId => $entry) {
            abort_unless(in_array((int) $generusId, $rosterIds, true), 422, 'Generus tidak termasuk dalam kelompok yang dipilih.');

            if (blank($entry['score'] ?? null) && blank($entry['result'] ?? null)) {
                continue;
            }

            $data = $this->applyGradeScale([
                'title' => $validated['title'],
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
                    'title' => $validated['title'],
                ],
                $data,
            );
        }

        return redirect()->route('ujians.batch', $request->only(['group_id', 'academic_year_id', 'semester_id', 'type']))
            ->with('success', 'Nilai ujian kelompok berhasil disimpan.');
    }

    /**
     * @return array<string, mixed>
     */
    private function formOptions(): array
    {
        return [
            'generus' => StudentScope::limit(Generus::query(), $this->studentIds(), 'id')->get(),
            'academicYears' => AcademicYear::where('is_active', true)->get(),
            'semesters' => Semester::where('is_active', true)->get(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function validateUjian(Request $request, ?Ujian $ujian = null): array
    {
        return $request->validate([
            'generus_id' => ['required', StudentScope::existsRule($request->user(), Ujian::MANAGE_PERMISSION)],
            'academic_year_id' => ['required', 'exists:academic_years,id'],
            'semester_id' => ['required', 'exists:semesters,id'],
            'title' => ['required', 'string', 'max:255'],
            'type' => ['required', 'string', 'max:50'],
            'score' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'result' => ['nullable', 'string', 'max:50'],
            'status' => ['required', 'string', 'max:50'],
            'notes' => ['nullable', 'string'],
        ]);
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

    private function studentIds(): ?array
    {
        return StudentScope::manageIds(request()->user(), Ujian::MANAGE_PERMISSION);
    }

    private function authorizeRecord(Ujian $ujian): void
    {
        StudentScope::authorize(request()->user(), Ujian::MANAGE_PERMISSION, $ujian->generus_id);
    }

    public function export(Request $request): BinaryFileResponse
    {
        return $this->exportSheet(new UjianSheet($request->user()));
    }

    public function import(Request $request): RedirectResponse
    {
        return $this->importSheet($request, new UjianSheet($request->user()));
    }
}
