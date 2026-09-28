<?php

namespace App\Http\Controllers;

use App\Models\AcademicYear;
use App\Models\Generus;
use App\Models\ReportCard;
use App\Models\Semester;
use App\Support\StudentScope;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ReportCardController extends Controller
{
    public function index(): View
    {
        return view('report-cards.index', [
            'reportCards' => StudentScope::limit(ReportCard::with(['generus', 'academicYear', 'semester'])->latest(), $this->studentIds())->paginate(25),
            ...$this->formOptions(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        ReportCard::create($this->validateReportCard($request));

        return redirect()->route('report-cards.index')->with('success', 'Rapor berhasil ditambahkan.');
    }

    public function edit(ReportCard $reportCard): View
    {
        $this->authorizeRecord($reportCard);

        return view('report-cards.edit', [
            'reportCard' => $reportCard,
            ...$this->formOptions(),
        ]);
    }

    public function update(Request $request, ReportCard $reportCard): RedirectResponse
    {
        $this->authorizeRecord($reportCard);

        $reportCard->update($this->validateReportCard($request, $reportCard));

        return redirect()->route('report-cards.index')->with('success', 'Rapor berhasil diperbarui.');
    }

    public function destroy(ReportCard $reportCard): RedirectResponse
    {
        $this->authorizeRecord($reportCard);

        $reportCard->delete();

        return redirect()->route('report-cards.index')->with('success', 'Rapor berhasil dihapus.');
    }

    /**
     * @return array<string, mixed>
     */
    private function formOptions(): array
    {
        return [
            'generus' => StudentScope::limit(Generus::query(), $this->studentIds(), 'id')->get(),
            'academicYears' => AcademicYear::where('is_active', true)->get(),
            'semesters' => Semester::where('is_active', true)->get(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function validateReportCard(Request $request, ?ReportCard $reportCard = null): array
    {
        return $request->validate([
            'generus_id' => [
                'required',
                StudentScope::existsRule($request->user(), 'manage-report-cards'),
                Rule::unique('report_cards')
                    ->where('academic_year_id', $request->input('academic_year_id'))
                    ->where('semester_id', $request->input('semester_id'))
                    ->ignore($reportCard?->id),
            ],
            'academic_year_id' => ['required', 'exists:academic_years,id'],
            'semester_id' => ['required', 'exists:semesters,id'],
            'final_score' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'predicate' => ['nullable', 'string', 'max:10'],
            'recommendation' => ['nullable', 'string'],
            'remarks' => ['nullable', 'string'],
        ], [
            'generus_id.unique' => 'Generus ini sudah memiliki rapor untuk tahun ajaran dan semester tersebut.',
        ]);
    }

    private function studentIds(): ?array
    {
        return StudentScope::ids(request()->user(), 'manage-report-cards');
    }

    private function authorizeRecord(ReportCard $reportCard): void
    {
        StudentScope::authorize(request()->user(), 'manage-report-cards', $reportCard->generus_id);
    }
}
