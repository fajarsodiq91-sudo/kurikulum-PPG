<?php

namespace App\Http\Controllers;

use App\Models\AcademicYear;
use App\Models\Generus;
use App\Models\GradeScale;
use App\Models\Group;
use App\Models\Munaqosah;
use App\Models\Semester;
use App\Support\StudentScope;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class MunaqosahController extends Controller
{
    public function index(): View
    {
        return view('munaqosahs.index', [
            'munaqosahs' => StudentScope::limit(Munaqosah::with(['generus', 'academicYear', 'semester'])->latest(), $this->studentIds())->paginate(25),
            ...$this->formOptions(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        Munaqosah::create($this->applyGradeScale($this->validateMunaqosah($request)));

        return redirect()->route('munaqosahs.index')->with('success', 'Munaqosah berhasil ditambahkan.');
    }

    public function edit(Munaqosah $munaqosah): View
    {
        $this->authorizeRecord($munaqosah);

        return view('munaqosahs.edit', [
            'munaqosah' => $munaqosah,
            ...$this->formOptions(),
        ]);
    }

    public function update(Request $request, Munaqosah $munaqosah): RedirectResponse
    {
        $this->authorizeRecord($munaqosah);

        $munaqosah->update($this->applyGradeScale($this->validateMunaqosah($request, $munaqosah)));

        return redirect()->route('munaqosahs.index')->with('success', 'Munaqosah berhasil diperbarui.');
    }

    public function destroy(Munaqosah $munaqosah): RedirectResponse
    {
        $this->authorizeRecord($munaqosah);

        $munaqosah->delete();

        return redirect()->route('munaqosahs.index')->with('success', 'Munaqosah berhasil dihapus.');
    }

    /**
     * One kelompok's munaqosah sheet for a given year, semester, and type: every generus in
     * the group with a score input, prefilled when already recorded.
     */
    public function batch(Request $request): View
    {
        $user = $request->user();

        $groups = Group::where('is_active', true)->manageableBy($user, Munaqosah::MANAGE_PERMISSION)->with('village')->orderBy('name')->get();

        $group = $request->filled('group_id')
            ? $groups->firstWhere('id', $request->integer('group_id'))
            : null;

        $academicYearId = $request->integer('academic_year_id') ?: null;
        $semesterId = $request->integer('semester_id') ?: null;
        $type = $request->string('type')->value() ?: 'semester-final';

        $roster = $group?->rosterFor($user, Munaqosah::MANAGE_PERMISSION)->get() ?? collect();

        $existing = $academicYearId && $semesterId
            ? Munaqosah::where('academic_year_id', $academicYearId)
                ->where('semester_id', $semesterId)
                ->where('type', $type)
                ->whereIn('generus_id', $roster->pluck('id'))
                ->get()
                ->keyBy('generus_id')
            : collect();

        return view('munaqosahs.batch', [
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

        $manageableGroupIds = Group::where('is_active', true)->manageableBy($user, Munaqosah::MANAGE_PERMISSION)->pluck('id')->all();

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
        $rosterIds = $group->rosterFor($user, Munaqosah::MANAGE_PERMISSION)->pluck('id')->map(fn (mixed $id): int => (int) $id)->all();

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

            Munaqosah::updateOrCreate(
                [
                    'generus_id' => (int) $generusId,
                    'academic_year_id' => $validated['academic_year_id'],
                    'semester_id' => $validated['semester_id'],
                    'type' => $validated['type'],
                ],
                $data,
            );
        }

        return redirect()->route('munaqosahs.batch', $request->only(['group_id', 'academic_year_id', 'semester_id', 'type']))
            ->with('success', 'Nilai munaqosah kelompok berhasil disimpan.');
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
    private function validateMunaqosah(Request $request, ?Munaqosah $munaqosah = null): array
    {
        return $request->validate([
            'generus_id' => [
                'required',
                StudentScope::existsRule($request->user(), Munaqosah::MANAGE_PERMISSION),
                Rule::unique('munaqosahs')
                    ->where('academic_year_id', $request->input('academic_year_id'))
                    ->where('semester_id', $request->input('semester_id'))
                    ->where('type', $request->input('type'))
                    ->ignore($munaqosah?->id),
            ],
            'academic_year_id' => ['required', 'exists:academic_years,id'],
            'semester_id' => ['required', 'exists:semesters,id'],
            'title' => ['required', 'string', 'max:255'],
            'type' => ['required', 'string', 'max:50'],
            'score' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'result' => ['nullable', 'string', 'max:50'],
            'status' => ['required', 'string', 'max:50'],
            'notes' => ['nullable', 'string'],
        ], [
            'generus_id.unique' => 'Generus ini sudah memiliki munaqosah untuk tahun ajaran, semester, dan tipe tersebut.',
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
        return StudentScope::ids(request()->user(), Munaqosah::MANAGE_PERMISSION);
    }

    private function authorizeRecord(Munaqosah $munaqosah): void
    {
        StudentScope::authorize(request()->user(), Munaqosah::MANAGE_PERMISSION, $munaqosah->generus_id);
    }
}
