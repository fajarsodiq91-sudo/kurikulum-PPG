<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\HandlesSheets;
use App\Models\ClassGrade;
use App\Models\Group;
use App\Models\LearningMaterial;
use App\Models\LearningSession;
use App\Models\Teacher;
use App\Models\Village;
use App\Support\Sheets\LearningSessionSheet;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
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
            'classGrades' => ClassGrade::where('is_active', true)->orderBy('sort_order')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'teacher_id' => ['required', 'exists:teachers,id'],
            'material_id' => ['nullable', 'exists:learning_materials,id'],
            'class_grade_id' => ['nullable', 'exists:class_grades,id'],
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
        $validated['class_grade_id'] = $validated['class_grade_id'] ?? ($validated['material_id'] ? $this->resolveClassGradeId($validated['material_id']) : null);

        LearningSession::create($validated);

        return redirect()->route('learning-sessions.index')->with('success', 'Jadwal KBM berhasil ditambahkan.');
    }

    /**
     * The teacher's own daily attendance session: found or created for the given date (today
     * by default) in their fixed group. When they teach more than one Kelas KBM, they must
     * say which one first.
     */
    public function today(Request $request): View|RedirectResponse
    {
        $teacher = $request->user()?->teacher;
        abort_if($teacher === null, 403, 'Akun Anda belum terhubung ke data Guru.');
        abort_if($teacher->group_id === null, 403, 'Guru ini belum ditempatkan di kelompok manapun.');

        $date = $request->filled('date') ? Carbon::parse($request->string('date')->value())->toDateString() : today()->toDateString();
        $classGrades = $teacher->classGrades;

        if ($request->filled('class_grade_id')) {
            $classGradeId = $classGrades->pluck('id')->contains($request->integer('class_grade_id'))
                ? $request->integer('class_grade_id')
                : abort(422, 'Kelas KBM tidak termasuk kelas yang Anda ajar.');
        } elseif ($classGrades->count() > 1) {
            return view('learning-sessions.today-pick-class', [
                'classGrades' => $classGrades,
                'date' => $date,
            ]);
        } else {
            $classGradeId = $classGrades->first()?->id;
        }

        $session = LearningSession::firstOrCreate([
            'teacher_id' => $teacher->id,
            'group_id' => $teacher->group_id,
            'level' => LearningSession::LEVEL_GROUP,
            'class_grade_id' => $classGradeId,
            'session_date' => $date,
        ], [
            'village_id' => $teacher->group->village_id,
            'status' => 'scheduled',
        ]);

        return redirect()->route('learning-sessions.attendance.edit', $session);
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
