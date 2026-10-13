<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\HandlesSheets;
use App\Models\ActivitySchedule;
use App\Models\OrganizationUnit;
use App\Support\Sheets\ActivityScheduleSheet;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ActivityScheduleController extends Controller
{
    use HandlesSheets;

    public function index(): View
    {
        return view('activity-schedules.index', [
            'activitySchedules' => ActivitySchedule::with('organizationUnit')->latest()->paginate(25),
            'organizationUnits' => OrganizationUnit::where('status', 'active')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'organization_unit_id' => ['required', 'exists:organization_units,id'],
            'title' => ['required', 'string', 'max:255'],
            'type' => ['required', 'string', 'max:50'],
            'scheduled_at' => ['required', 'date'],
            'end_at' => ['nullable', 'date'],
            'location' => ['nullable', 'string', 'max:255'],
            'status' => ['required', 'string', 'max:50'],
            'notes' => ['nullable', 'string'],
        ]);

        ActivitySchedule::create($validated);

        return redirect()->route('activity-schedules.index')->with('success', 'Jadwal kegiatan berhasil ditambahkan.');
    }

    public function export(): BinaryFileResponse
    {
        return $this->exportSheet(new ActivityScheduleSheet);
    }

    public function import(Request $request): RedirectResponse
    {
        return $this->importSheet($request, new ActivityScheduleSheet);
    }
}
