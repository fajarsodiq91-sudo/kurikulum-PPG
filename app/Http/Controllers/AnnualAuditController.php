<?php

namespace App\Http\Controllers;

use App\Models\AcademicYear;
use App\Models\AnnualAudit;
use App\Models\Generus;
use App\Models\Semester;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AnnualAuditController extends Controller
{
    public function index(): View
    {
        return view('annual-audits.index', [
            'annualAudits' => AnnualAudit::with(['generus', 'academicYear', 'semester'])->latest()->get(),
            'generus' => Generus::all(),
            'academicYears' => AcademicYear::where('is_active', true)->get(),
            'semesters' => Semester::where('is_active', true)->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'generus_id' => ['required', 'exists:generus,id'],
            'academic_year_id' => ['required', 'exists:academic_years,id'],
            'semester_id' => ['required', 'exists:semesters,id'],
            'audit_type' => ['required', 'string', 'max:50'],
            'overall_status' => ['required', 'string', 'max:50'],
            'summary' => ['nullable', 'string'],
            'recommendation' => ['nullable', 'string'],
        ]);

        AnnualAudit::create($validated);

        return redirect()->route('annual-audits.index')->with('success', 'Audit tahunan berhasil ditambahkan.');
    }
}
