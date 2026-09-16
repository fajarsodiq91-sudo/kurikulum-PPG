<?php

namespace App\Http\Controllers;

use App\Models\FollowUp;
use App\Models\Generus;
use App\Models\Teacher;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class FollowUpController extends Controller
{
    public function index(): View
    {
        return view('follow-ups.index', [
            'followUps' => FollowUp::with(['generus', 'teacher'])->latest()->get(),
            'generus' => Generus::all(),
            'teachers' => Teacher::where('status', 'active')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'generus_id' => ['required', 'exists:generus,id'],
            'teacher_id' => ['required', 'exists:teachers,id'],
            'title' => ['required', 'string', 'max:255'],
            'priority' => ['required', 'string', 'max:20'],
            'follow_up_date' => ['required', 'date'],
            'status' => ['required', 'string', 'max:50'],
            'notes' => ['nullable', 'string'],
            'next_action' => ['nullable', 'string'],
        ]);

        FollowUp::create($validated);

        return redirect()->route('follow-ups.index')->with('success', 'Follow up berhasil ditambahkan.');
    }
}
