<?php

namespace App\Http\Controllers;

use App\Models\LearningMaterial;
use App\Models\LearningSession;
use App\Models\Teacher;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LearningSessionController extends Controller
{
    public function index(): View
    {
        return view('learning-sessions.index', [
            'sessions' => LearningSession::with(['teacher', 'material'])->latest()->get(),
            'teachers' => Teacher::where('status', 'active')->get(),
            'materials' => LearningMaterial::where('is_active', true)->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'teacher_id' => ['required', 'exists:teachers,id'],
            'material_id' => ['required', 'exists:learning_materials,id'],
            'session_date' => ['required', 'date'],
            'start_time' => ['nullable', 'date_format:H:i'],
            'end_time' => ['nullable', 'date_format:H:i'],
            'location' => ['nullable', 'string', 'max:255'],
            'status' => ['required', 'string', 'max:50'],
            'notes' => ['nullable', 'string'],
        ]);

        LearningSession::create($validated);

        return redirect()->route('learning-sessions.index')->with('success', 'Jadwal KBM berhasil ditambahkan.');
    }
}
