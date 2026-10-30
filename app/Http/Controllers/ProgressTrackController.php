<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\HandlesSheets;
use App\Models\AcademicYear;
use App\Models\Generus;
use App\Models\ProgressTrack;
use App\Models\Semester;
use App\Support\Sheets\ProgressTrackSheet;
use App\Support\StudentScope;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ProgressTrackController extends Controller
{
    use HandlesSheets;

    public function index(Request $request): View
    {
        $search = trim((string) $request->query('q', ''));
        $academicYearId = $request->query('academic_year_id', '');
        $semesterId = $request->query('semester_id', '');
        $status = $request->query('overall_status', '');

        $progressTracks = StudentScope::limit(
            ProgressTrack::with(['generus', 'academicYear', 'semester'])
                ->when($search !== '', fn ($query) => $query->whereHas('generus', fn ($query) => $query->where('full_name', 'like', '%'.addcslashes($search, '%_\\').'%')))
                ->when($academicYearId !== '', fn ($query) => $query->where('academic_year_id', $academicYearId))
                ->when($semesterId !== '', fn ($query) => $query->where('semester_id', $semesterId))
                ->when($status !== '', fn ($query) => $query->where('overall_status', $status))
                ->latest(),
            StudentScope::viewIds($request->user(), 'manage-progress-tracking')
        )->paginate(25)->withQueryString();

        return view('progress-tracks.index', [
            'progressTracks' => $progressTracks,
            'generus' => StudentScope::limit(Generus::query(), $this->studentIds(), 'id')->get(),
            'academicYears' => AcademicYear::where('is_active', true)->get(),
            'semesters' => Semester::where('is_active', true)->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'generus_id' => ['required', StudentScope::existsRule($request->user(), 'manage-progress-tracking')],
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

    public function update(Request $request, ProgressTrack $progress_track): RedirectResponse
    {
        StudentScope::authorize($request->user(), 'manage-progress-tracking', $progress_track->generus_id);

        $validated = $request->validate([
            'generus_id' => ['required', StudentScope::existsRule($request->user(), 'manage-progress-tracking')],
            'academic_year_id' => ['required', 'exists:academic_years,id'],
            'semester_id' => ['required', 'exists:semesters,id'],
            'period_label' => ['required', 'string', 'max:50'],
            'overall_status' => ['required', 'string', 'max:50'],
            'score' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'notes' => ['nullable', 'string'],
            'next_goal' => ['nullable', 'string'],
        ]);

        $progress_track->update($validated);

        return redirect()->route('progress-tracks.index')->with('success', 'Progress tracking berhasil diperbarui.');
    }

    public function destroy(ProgressTrack $progress_track): RedirectResponse
    {
        StudentScope::authorize(request()->user(), 'manage-progress-tracking', $progress_track->generus_id);

        try {
            $progress_track->delete();
        } catch (QueryException $e) {
            if ($e->getCode() === '23000') {
                return redirect()->route('progress-tracks.index')->withErrors([
                    'delete' => 'Progress tracking tidak dapat dihapus karena masih digunakan oleh data lain.',
                ]);
            }

            throw $e;
        }

        return redirect()->route('progress-tracks.index')->with('success', 'Progress tracking berhasil dihapus.');
    }

    private function studentIds(): ?array
    {
        return StudentScope::manageIds(request()->user(), 'manage-progress-tracking');
    }

    public function export(Request $request): BinaryFileResponse
    {
        return $this->exportSheet(new ProgressTrackSheet($request->user()));
    }

    public function import(Request $request): RedirectResponse
    {
        return $this->importSheet($request, new ProgressTrackSheet($request->user()));
    }
}
