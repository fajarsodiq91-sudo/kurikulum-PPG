<?php

namespace App\Http\Controllers;

use App\Models\Group;
use App\Models\LearningMaterial;
use App\Models\LearningSession;
use App\Models\Teacher;
use App\Models\Village;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class LearningSessionController extends Controller
{
    public function index(): View
    {
        return view('learning-sessions.index', [
            'sessions' => LearningSession::with(['teacher', 'material', 'village', 'group'])->latest()->paginate(25),
            'attendableIds' => LearningSession::query()->attendableBy(request()->user())->pluck('id')->all(),
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

        LearningSession::create($validated);

        return redirect()->route('learning-sessions.index')->with('success', 'Jadwal KBM berhasil ditambahkan.');
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
}
