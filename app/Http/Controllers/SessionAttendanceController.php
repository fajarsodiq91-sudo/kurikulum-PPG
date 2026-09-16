<?php

namespace App\Http\Controllers;

use App\Models\Generus;
use App\Models\SessionAttendance;
use App\Models\LearningSession;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SessionAttendanceController extends Controller
{
    public function index(): View
    {
        return view('session-attendances.index', [
            'attendances' => SessionAttendance::with(['learningSession', 'generus'])->latest()->get(),
            'sessions' => LearningSession::latest()->get(),
            'generus' => Generus::where('status', 'active')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'learning_session_id' => ['required', 'exists:learning_sessions,id'],
            'generus_id' => ['required', 'exists:generus,id'],
            'status' => ['required', 'string', 'max:50'],
            'notes' => ['nullable', 'string'],
        ]);

        SessionAttendance::create($validated);

        return redirect()->route('session-attendances.index')->with('success', 'Presensi berhasil ditambahkan.');
    }
}
