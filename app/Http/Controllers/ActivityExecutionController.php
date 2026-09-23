<?php

namespace App\Http\Controllers;

use App\Models\ActivityExecution;
use App\Models\ActivitySchedule;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ActivityExecutionController extends Controller
{
    public function index(): View
    {
        return view('activity-executions.index', [
            'activityExecutions' => ActivityExecution::with('activitySchedule')->latest()->paginate(25),
            'activitySchedules' => ActivitySchedule::latest()->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'activity_schedule_id' => ['required', 'exists:activity_schedules,id'],
            'actual_date' => ['required', 'date'],
            'status' => ['required', 'string', 'max:50'],
            'attendance_count' => ['required', 'integer', 'min:0'],
            'outcome' => ['required', 'string'],
            'notes' => ['nullable', 'string'],
        ]);

        ActivityExecution::create($validated);

        return redirect()->route('activity-executions.index')->with('success', 'Pelaksanaan kegiatan berhasil dicatat.');
    }
}
