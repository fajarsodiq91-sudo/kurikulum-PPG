<?php

namespace App\Http\Controllers;

use App\Models\AcademicYear;
use App\Models\Generus;
use App\Models\ProgressTrack;
use App\Models\Semester;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProgressTrackController extends Controller
{
    public function index(): View
    {
        return view('progress-tracks.index', [
            'progressTracks' => ProgressTrack::with(['generus', 'academicYear', 'semester'])->latest()->get(),
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
            'period_label' => ['required', 'string', 'max:50'],
            'overall_status' => ['required', 'string', 'max:50'],
            'score' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'notes' => ['nullable', 'string'],
            'next_goal' => ['nullable', 'string'],
        ]);

        ProgressTrack::create($validated);

        return redirect()->route('progress-tracks.index')->with('success', 'Progress tracking berhasil ditambahkan.');
    }
}
