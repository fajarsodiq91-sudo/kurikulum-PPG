<?php

namespace App\Http\Controllers;

use App\Models\Assignment;
use App\Models\OrganizationUnit;
use App\Models\Teacher;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AssignmentController extends Controller
{
    public function index(): View
    {
        return view('assignments.index', [
            'assignments' => Assignment::with(['organizationUnit', 'teacher'])->latest()->get(),
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
}
