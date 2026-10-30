<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\HandlesSheets;
use App\Models\AcademicYear;
use App\Models\Training;
use App\Support\Sheets\TrainingSheet;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class TrainingController extends Controller
{
    use HandlesSheets;

    public function index(Request $request): View
    {
        $search = trim((string) $request->query('q', ''));
        $type = $request->query('type', '');
        $status = $request->query('status', '');
        $academicYearId = $request->query('academic_year_id', '');

        $trainings = Training::with('academicYear')
            ->when($search !== '', fn ($query) => $query->where('title', 'like', '%'.addcslashes($search, '%_\\').'%'))
            ->when($type !== '', fn ($query) => $query->where('type', $type))
            ->when($status !== '', fn ($query) => $query->where('status', $status))
            ->when($academicYearId !== '', fn ($query) => $query->where('academic_year_id', $academicYearId))
            ->latest()
            ->paginate(25)
            ->withQueryString();

        return view('trainings.index', [
            'trainings' => $trainings,
            'academicYears' => AcademicYear::where('is_active', true)->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'type' => ['required', 'string', 'max:50'],
            'academic_year_id' => ['required', 'exists:academic_years,id'],
            'scheduled_at' => ['nullable', 'date'],
            'location' => ['nullable', 'string', 'max:255'],
            'status' => ['required', 'string', 'max:50'],
            'notes' => ['nullable', 'string'],
        ]);

        Training::create($validated);

        return redirect()->route('trainings.index')->with('success', 'Pelatihan berhasil ditambahkan.');
    }

    public function export(): BinaryFileResponse
    {
        return $this->exportSheet(new TrainingSheet);
    }

    public function import(Request $request): RedirectResponse
    {
        return $this->importSheet($request, new TrainingSheet);
    }
}
