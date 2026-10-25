<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\HandlesSheets;
use App\Models\AcademicYear;
use App\Models\AnnualAudit;
use App\Models\Generus;
use App\Models\Semester;
use App\Support\Sheets\AnnualAuditSheet;
use App\Support\StudentScope;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class AnnualAuditController extends Controller
{
    use HandlesSheets;

    public function index(): View
    {
        return view('annual-audits.index', [
            'annualAudits' => StudentScope::limit(AnnualAudit::with(['generus', 'academicYear', 'semester'])->latest(), StudentScope::viewIds(request()->user(), 'manage-annual-audit'))->paginate(25),
            'generus' => StudentScope::limit(Generus::query(), $this->studentIds(), 'id')->get(),
            'academicYears' => AcademicYear::where('is_active', true)->get(),
            'semesters' => Semester::where('is_active', true)->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'generus_id' => ['required', StudentScope::existsRule($request->user(), 'manage-annual-audit')],
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

    private function studentIds(): ?array
    {
        return StudentScope::manageIds(request()->user(), 'manage-annual-audit');
    }

    public function export(Request $request): BinaryFileResponse
    {
        return $this->exportSheet(new AnnualAuditSheet($request->user()));
    }

    public function import(Request $request): RedirectResponse
    {
        return $this->importSheet($request, new AnnualAuditSheet($request->user()));
    }
}
