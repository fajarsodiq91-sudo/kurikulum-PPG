<?php

namespace App\Http\Controllers;

use App\Models\AcademicYear;
use App\Models\Generus;
use App\Models\ReportCard;
use App\Models\Semester;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ReportCardController extends Controller
{
    public function index(): View
    {
        return view('report-cards.index', [
            'reportCards' => ReportCard::with(['generus', 'academicYear', 'semester'])->latest()->get(),
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
            'final_score' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'predicate' => ['nullable', 'string', 'max:10'],
            'recommendation' => ['nullable', 'string'],
            'remarks' => ['nullable', 'string'],
        ]);

        ReportCard::create($validated);

        return redirect()->route('report-cards.index')->with('success', 'Rapor berhasil ditambahkan.');
    }
}
