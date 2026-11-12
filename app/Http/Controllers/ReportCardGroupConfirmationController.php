<?php

namespace App\Http\Controllers;

use App\Models\Generus;
use App\Models\ReportCardGroupConfirmation;
use App\Models\ReportCardPublication;
use App\Support\GradePromotionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * A Pelaksana PPG Kelompok's final say, per semester-2 rapor publication, on who in their
 * kelompok is "tinggal kelas" — everyone else promotes to the next kelas KBM the moment this
 * is confirmed (see GradePromotionService). Once every kelompok under the same
 * ReportCardPublication has confirmed, that publication publishes.
 */
class ReportCardGroupConfirmationController extends Controller
{
    public function index(Request $request): View
    {
        $groupIds = $request->user()->scopedPlacementIds(ReportCardGroupConfirmation::CONFIRM_PERMISSION)['group_id'] ?? [];

        return view('report-card-group-confirmations.index', [
            'confirmations' => ReportCardGroupConfirmation::with(['publication.academicYear', 'publication.semester', 'group'])
                ->whereIn('group_id', $groupIds)
                ->latest()
                ->get(),
        ]);
    }

    public function edit(Request $request, ReportCardGroupConfirmation $reportCardGroupConfirmation): View
    {
        $this->ensureOwnGroup($request, $reportCardGroupConfirmation);

        return view('report-card-group-confirmations.edit', [
            'confirmation' => $reportCardGroupConfirmation->load(['publication.academicYear', 'publication.semester', 'group', 'retainedGenerus']),
            'roster' => Generus::where('status', 'active')
                ->whereHas('assignments', fn ($query) => $query->whereNull('ended_at')->where('group_id', $reportCardGroupConfirmation->group_id))
                ->with('learningClass')
                ->orderBy('full_name')
                ->get(),
        ]);
    }

    public function update(Request $request, ReportCardGroupConfirmation $reportCardGroupConfirmation): RedirectResponse
    {
        $this->ensureOwnGroup($request, $reportCardGroupConfirmation);

        abort_unless($reportCardGroupConfirmation->isPending(), 422, 'Konfirmasi ini sudah selesai.');

        $validated = $request->validate([
            'retained_generus_ids' => ['nullable', 'array'],
            'retained_generus_ids.*' => ['integer'],
        ]);

        $rosterIds = Generus::where('status', 'active')
            ->whereHas('assignments', fn ($query) => $query->whereNull('ended_at')->where('group_id', $reportCardGroupConfirmation->group_id))
            ->pluck('id');

        $retainedIds = array_values(array_intersect($validated['retained_generus_ids'] ?? [], $rosterIds->all()));

        $reportCardGroupConfirmation->retainedGenerus()->sync($retainedIds);

        foreach ($rosterIds as $generusId) {
            if (in_array($generusId, $retainedIds, true)) {
                continue;
            }

            $generus = Generus::find($generusId);

            if ($generus?->learningClass !== null) {
                GradePromotionService::promote($generus, GradePromotionService::nextClassGradeFor($generus->learningClass));
            }
        }

        $reportCardGroupConfirmation->update([
            'status' => ReportCardGroupConfirmation::STATUS_CONFIRMED,
            'confirmed_by_id' => $request->user()->id,
            'confirmed_at' => now(),
        ]);

        $publication = $reportCardGroupConfirmation->publication;

        if ($publication->allGroupsConfirmed()) {
            $publication->update(['status' => ReportCardPublication::STATUS_PUBLISHED, 'published_at' => now()]);
        }

        return redirect()->route('report-card-group-confirmations.index')->with('success', 'Konfirmasi kenaikan kelas berhasil disimpan.');
    }

    private function ensureOwnGroup(Request $request, ReportCardGroupConfirmation $confirmation): void
    {
        $groupIds = $request->user()->scopedPlacementIds(ReportCardGroupConfirmation::CONFIRM_PERMISSION)['group_id'] ?? [];

        abort_unless(in_array($confirmation->group_id, $groupIds, true), 403);
    }
}
