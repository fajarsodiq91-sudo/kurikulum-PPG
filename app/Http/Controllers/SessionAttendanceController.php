<?php

namespace App\Http\Controllers;

use App\Models\Generus;
use App\Models\LearningSession;
use App\Models\SessionAttendance;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class SessionAttendanceController extends Controller
{
    public function index(): View
    {
        return view('session-attendances.index', [
            'attendances' => SessionAttendance::with(['learningSession', 'generus'])->latest()->paginate(25),
            'sessions' => LearningSession::latest()->get(),
            'generus' => Generus::where('status', 'active')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'learning_session_id' => ['required', 'exists:learning_sessions,id'],
            'generus_id' => [
                'required',
                'exists:generus,id',
                Rule::unique('session_attendances')->where('learning_session_id', $request->input('learning_session_id')),
            ],
            'status' => ['required', 'string', 'max:50'],
            'notes' => ['nullable', 'string'],
        ], [
            'generus_id.unique' => 'Presensi generus ini untuk sesi tersebut sudah dicatat.',
        ]);

        SessionAttendance::create($validated);

        return redirect()->route('session-attendances.index')->with('success', 'Presensi berhasil ditambahkan.');
    }
}
