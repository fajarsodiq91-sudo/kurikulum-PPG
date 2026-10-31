<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\HandlesSheets;
use App\Models\Group;
use App\Models\LearningMaterial;
use App\Models\LearningSession;
use App\Models\Teacher;
use App\Models\Village;
use App\Support\Sheets\LearningSessionSheet;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class LearningSessionController extends Controller
{
    use HandlesSheets;

    public function index(Request $request): View
    {
        $user = $request->user();
        $canManageAllSessions = $user?->hasGlobalAccess(LearningSession::MANAGE_PERMISSION) ?? false;

        return view('learning-sessions.index', [
            'sessions' => LearningSession::visibleTo($user)->with(['teacher', 'material', 'village', 'group'])->latest()->paginate(25),
            'attendableIds' => LearningSession::query()->attendableBy($user)->pluck('id')->all(),
            'canManageAllSessions' => $canManageAllSessions,
            'completableIds' => $canManageAllSessions ? null : LearningSession::query()->attendableBy($user)->pluck('id')->all(),
            'villages' => Village::where('is_active', true)->orderBy('name')->get(),
            'groups' => Group::where('is_active', true)->orderBy('name')->get(),
            'teachers' => Teacher::where('status', 'active')->get(),
            'materials' => LearningMaterial::where('is_active', true)->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'teacher_id' => ['required', 'exists:teachers,id'],
            'material_id' => ['required', 'exists:learning_materials,id'],
            'level' => ['required', Rule::in([LearningSession::LEVEL_VILLAGE, LearningSession::LEVEL_GROUP])],
            'village_id' => ['required_if:level,'.LearningSession::LEVEL_VILLAGE, 'nullable', 'exists:villages,id'],
            'group_id' => ['required_if:level,'.LearningSession::LEVEL_GROUP, 'nullable', 'exists:groups,id'],
            'session_date' => ['required', 'date'],
            'start_time' => ['nullable', 'date_format:H:i'],
            'end_time' => ['nullable', 'date_format:H:i'],
            'location' => ['nullable', 'string', 'max:255'],
            'status' => ['required', 'string', 'max:50'],
            'notes' => ['nullable', 'string'],
        ]);

        $validated = $this->keepOnlyLevelPlacement($validated);
        $validated['class_grade_id'] = $this->resolveClassGradeId($validated['material_id']);

        LearningSession::create($validated);

        return redirect()->route('learning-sessions.index')->with('success', 'Jadwal KBM berhasil ditambahkan.');
    }

    /**
     * One national weekly schedule: a material (already tagged with its week number) and a
     * calendar date, generated into every active village or group at once. Teacher and time
     * are left for each village/group to complete afterwards.
     */
    public function generate(): View
    {
        return view('learning-sessions.generate', [
            'materials' => LearningMaterial::with('materialChapter.materialCategory.semester.academicYear')
                ->where('is_active', true)
                ->orderBy('week_number')
                ->get(),
            'groupCount' => Group::where('is_active', true)->count(),
            'villageCount' => Village::where('is_active', true)->count(),
        ]);
    }

    public function generateStore(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'material_id' => ['required', 'exists:learning_materials,id'],
            'session_date' => ['required', 'date'],
            'level' => ['required', Rule::in([LearningSession::LEVEL_VILLAGE, LearningSession::LEVEL_GROUP])],
            'status' => ['required', 'string', 'max:50'],
        ]);

        $count = $validated['level'] === LearningSession::LEVEL_GROUP
            ? $this->generateForGroups($validated)
            : $this->generateForVillages($validated);

        $target = $validated['level'] === LearningSession::LEVEL_GROUP ? 'kelompok' : 'desa';

        return redirect()->route('learning-sessions.index')
            ->with('success', "Jadwal KBM berhasil dibuat untuk {$count} {$target}. Guru dan jam pelaksanaan dapat dilengkapi oleh masing-masing {$target}.");
    }

    /**
     * @param  array<string, mixed>  $validated
     */
    private function generateForGroups(array $validated): int
    {
        $groups = Group::where('is_active', true)->get();
        $classGradeId = $this->resolveClassGradeId($validated['material_id']);

        foreach ($groups as $group) {
            LearningSession::updateOrCreate(
                ['material_id' => $validated['material_id'], 'group_id' => $group->id, 'level' => LearningSession::LEVEL_GROUP, 'class_grade_id' => $classGradeId],
                ['village_id' => $group->village_id, 'session_date' => $validated['session_date'], 'status' => $validated['status']],
            );
        }

        return $groups->count();
    }

    /**
     * @param  array<string, mixed>  $validated
     */
    private function generateForVillages(array $validated): int
    {
        $villages = Village::where('is_active', true)->get();
        $classGradeId = $this->resolveClassGradeId($validated['material_id']);

        foreach ($villages as $village) {
            LearningSession::updateOrCreate(
                ['material_id' => $validated['material_id'], 'village_id' => $village->id, 'level' => LearningSession::LEVEL_VILLAGE, 'group_id' => null, 'class_grade_id' => $classGradeId],
                ['session_date' => $validated['session_date'], 'status' => $validated['status']],
            );
        }

        return $villages->count();
    }

    /**
     * The Kelas KBM a material belongs to, resolved via its chapter's category.
     */
    private function resolveClassGradeId(int $materialId): ?int
    {
        return LearningMaterial::with('materialChapter.materialCategory')
            ->find($materialId)?->materialChapter?->materialCategory?->class_grade_id;
    }

    /**
     * A village/group completes its own generated session with a teacher and time; the
     * national schedule's material, date, and placement stay fixed here.
     */
    public function edit(Request $request, LearningSession $learningSession): View
    {
        $this->authorizeCompletion($request, $learningSession);

        return view('learning-sessions.edit', [
            'session' => $learningSession,
            'teachers' => Teacher::where('status', 'active')->get(),
        ]);
    }

    public function update(Request $request, LearningSession $learningSession): RedirectResponse
    {
        $this->authorizeCompletion($request, $learningSession);

        $validated = $request->validate([
            'teacher_id' => ['required', 'exists:teachers,id'],
            'start_time' => ['nullable', 'date_format:H:i'],
            'end_time' => ['nullable', 'date_format:H:i'],
            'location' => ['nullable', 'string', 'max:255'],
            'status' => ['required', 'string', 'max:50'],
            'notes' => ['nullable', 'string'],
        ]);

        $learningSession->update($validated);

        return redirect()->route('learning-sessions.index')->with('success', 'Jadwal KBM berhasil dilengkapi.');
    }

    public function destroy(Request $request, LearningSession $learningSession): RedirectResponse
    {
        abort_unless($request->user()?->hasGlobalAccess(LearningSession::MANAGE_PERMISSION), 403);

        $learningSession->delete();

        return redirect()->route('learning-sessions.index')->with('success', 'Jadwal KBM berhasil dihapus.');
    }

    /**
     * A village-level session keeps only its village; a group-level one only its group and that group's village.
     *
     * @param  array<string, mixed>  $validated
     * @return array<string, mixed>
     */
    private function keepOnlyLevelPlacement(array $validated): array
    {
        if ($validated['level'] === LearningSession::LEVEL_VILLAGE) {
            $validated['group_id'] = null;
        } else {
            $validated['village_id'] = Group::whereKey($validated['group_id'])->value('village_id');
        }

        return $validated;
    }

    private function authorizeCompletion(Request $request, LearningSession $learningSession): void
    {
        abort_unless($learningSession->isCompletableBy($request->user()), 403);
    }

    public function export(Request $request): BinaryFileResponse
    {
        return $this->exportSheet(new LearningSessionSheet($request->user()));
    }
}
