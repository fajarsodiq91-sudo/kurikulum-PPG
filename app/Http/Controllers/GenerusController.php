<?php

namespace App\Http\Controllers;

use App\Models\AcademicYear;
use App\Models\Generus;
use App\Models\GenerusAssignment;
use App\Models\Group;
use App\Models\Level;
use App\Models\Region;
use App\Models\Village;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class GenerusController extends Controller
{
    public function index(): View
    {
        return view('generus.index', [
            'generus' => Generus::with('assignments')->latest()->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'registration_number' => ['required', 'string', 'max:50', 'unique:generus,registration_number'],
            'full_name' => ['required', 'string', 'max:255'],
            'gender' => ['nullable', 'string', 'max:50'],
            'birth_date' => ['nullable', 'date'],
            'status' => ['nullable', 'string', 'max:50'],
            'region_id' => ['required', 'exists:regions,id'],
            'village_id' => ['required', 'exists:villages,id'],
            'group_id' => ['required', 'exists:groups,id'],
            'level_id' => ['required', 'exists:levels,id'],
            'academic_year_id' => ['required', 'exists:academic_years,id'],
            'assignment_status' => ['required', 'string', 'max:50'],
            'notes' => ['nullable', 'string'],
        ]);

        $generus = Generus::create([
            'registration_number' => $validated['registration_number'],
            'full_name' => $validated['full_name'],
            'gender' => $validated['gender'] ?? null,
            'birth_date' => $validated['birth_date'] ?? null,
            'status' => $validated['status'] ?? 'active',
            'notes' => $validated['notes'] ?? null,
        ]);

        GenerusAssignment::create([
            'generus_id' => $generus->id,
            'region_id' => $validated['region_id'],
            'village_id' => $validated['village_id'],
            'group_id' => $validated['group_id'],
            'level_id' => $validated['level_id'],
            'academic_year_id' => $validated['academic_year_id'],
            'status' => $validated['assignment_status'],
            'assigned_at' => now()->toDateString(),
            'notes' => $validated['notes'] ?? null,
        ]);

        return redirect()->route('generus.index')->with('success', 'Generus berhasil ditambahkan.');
    }

    public function create(): View
    {
        return view('generus.create', [
            'regions' => Region::where('is_active', true)->get(),
            'villages' => Village::where('is_active', true)->get(),
            'groups' => Group::where('is_active', true)->get(),
            'levels' => Level::where('is_active', true)->orderBy('sort_order')->get(),
            'academicYears' => AcademicYear::where('is_active', true)->get(),
        ]);
    }
}
