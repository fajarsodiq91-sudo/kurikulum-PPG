<?php

namespace App\Http\Controllers;

use App\Models\FollowUp;
use App\Models\Generus;
use App\Models\Teacher;
use App\Support\StudentScope;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class FollowUpController extends Controller
{
    public function index(): View
    {
        return view('follow-ups.index', [
            'followUps' => StudentScope::limit(FollowUp::with(['generus', 'teacher'])->latest(), $this->studentIds())->paginate(25),
            'generus' => StudentScope::limit(Generus::query(), $this->studentIds(), 'id')->get(),
            'teachers' => Teacher::where('status', 'active')
                ->when($this->studentIds() !== null, fn ($query) => $query->whereKey(request()->user()->teacher_id))
                ->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        if ($this->studentIds() !== null) {
            $request->merge(['teacher_id' => $request->user()->teacher_id]);
        }

        $validated = $request->validate([
            'generus_id' => ['required', StudentScope::existsRule($request->user(), 'manage-follow-ups')],
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

    private function studentIds(): ?array
    {
        return StudentScope::ids(request()->user(), 'manage-follow-ups');
    }
}
