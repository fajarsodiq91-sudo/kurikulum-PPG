<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\HandlesSheets;
use App\Models\AcademicYear;
use App\Models\Generus;
use App\Models\Milestone;
use App\Models\Semester;
use App\Support\Sheets\MilestoneSheet;
use App\Support\StudentScope;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class MilestoneController extends Controller
{
    use HandlesSheets;

    public function index(): View
    {
        return view('milestones.index', [
            'milestones' => StudentScope::limit(Milestone::with(['generus', 'academicYear', 'semester'])->latest(), $this->studentIds())->paginate(25),
            'generus' => StudentScope::limit(Generus::query(), $this->studentIds(), 'id')->get(),
            'academicYears' => AcademicYear::where('is_active', true)->get(),
            'semesters' => Semester::where('is_active', true)->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'generus_id' => ['required', StudentScope::existsRule($request->user(), 'manage-milestones')],
            'academic_year_id' => ['required', 'exists:academic_years,id'],
            'semester_id' => ['required', 'exists:semesters,id'],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'achievement_date' => ['required', 'date'],
            'status' => ['required', 'string', 'max:50'],
            'score' => ['nullable', 'numeric', 'min:0', 'max:100'],
        ]);

        Milestone::create($validated);

        return redirect()->route('milestones.index')->with('success', 'Milestone berhasil ditambahkan.');
    }

    private function studentIds(): ?array
    {
        return StudentScope::ids(request()->user(), 'manage-milestones');
    }

    public function export(Request $request): BinaryFileResponse
    {
        return $this->exportSheet(new MilestoneSheet($request->user()));
    }

    public function import(Request $request): RedirectResponse
    {
        return $this->importSheet($request, new MilestoneSheet($request->user()));
    }
}
