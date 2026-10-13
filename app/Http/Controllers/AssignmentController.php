<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\HandlesSheets;
use App\Models\Assignment;
use App\Models\OrganizationUnit;
use App\Models\Teacher;
use App\Support\Sheets\AssignmentSheet;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class AssignmentController extends Controller
{
    use HandlesSheets;

    public function index(): View
    {
        return view('assignments.index', [
            'assignments' => Assignment::with(['organizationUnit', 'teacher'])->latest()->paginate(25),
            'organizationUnits' => OrganizationUnit::where('status', 'active')->get(),
            'teachers' => Teacher::where('status', 'active')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'organization_unit_id' => ['required', 'exists:organization_units,id'],
            'teacher_id' => ['required', 'exists:teachers,id'],
            'assignment_title' => ['required', 'string', 'max:255'],
            'assignment_type' => ['required', 'string', 'max:50'],
            'start_date' => ['required', 'date'],
            'end_date' => ['nullable', 'date'],
            'status' => ['required', 'string', 'max:50'],
            'responsibility' => ['nullable', 'string'],
        ]);

        Assignment::create($validated);

        return redirect()->route('assignments.index')->with('success', 'Penugasan berhasil ditambahkan.');
    }

    public function export(): BinaryFileResponse
    {
        return $this->exportSheet(new AssignmentSheet);
    }

    public function import(Request $request): RedirectResponse
    {
        return $this->importSheet($request, new AssignmentSheet);
    }
}
