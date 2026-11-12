<?php

namespace App\Http\Controllers;

use App\Models\AcademicYear;
use App\Models\Generus;
use App\Models\Group;
use App\Models\Munaqosah;
use App\Models\ReportCardGroupConfirmation;
use App\Models\ReportCardPublication;
use App\Models\Semester;
use App\Models\User;
use App\Notifications\ReportCardPublicationRequested;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * PPG Daerah-only: confirming that rapor for one academic year + semester should be
 * published. At semester 2 this fans out one ReportCardGroupConfirmation per kelompok that
 * has active generus and waits for all of them before actually publishing (kenaikan kelas
 * only lands once a kelompok confirms — see ReportCardGroupConfirmationController); at
 * semester 1 there is nothing to confirm, so it publishes immediately.
 */
class ReportCardPublicationController extends Controller
{
    public function index(Request $request): View
    {
        $academicYears = AcademicYear::where('is_active', true)->get();
        $semesters = Semester::where('is_active', true)->get();

        return view('report-card-publications.index', [
            'academicYears' => $academicYears,
            'semesters' => $semesters,
            'publications' => ReportCardPublication::with(['academicYear', 'semester', 'groupConfirmations.group'])
                ->whereIn('academic_year_id', $academicYears->pluck('id'))
                ->latest()
                ->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'academic_year_id' => ['required', 'exists:academic_years,id'],
            'semester_id' => ['required', 'exists:semesters,id', Rule::unique('report_card_publications', 'semester_id')->where('academic_year_id', $request->input('academic_year_id'))],
        ]);

        $semester = Semester::findOrFail($validated['semester_id']);
        $requiresGroupConfirmation = $semester->sort_order === Munaqosah::PROMOTING_SEMESTER_SORT_ORDER;

        $publication = DB::transaction(function () use ($validated, $request, $requiresGroupConfirmation): ReportCardPublication {
            $publication = ReportCardPublication::create([
                'academic_year_id' => $validated['academic_year_id'],
                'semester_id' => $validated['semester_id'],
                'requires_group_confirmation' => $requiresGroupConfirmation,
                'status' => $requiresGroupConfirmation
                    ? ReportCardPublication::STATUS_PENDING_GROUP_CONFIRMATION
                    : ReportCardPublication::STATUS_PUBLISHED,
                'initiated_by_id' => $request->user()->id,
                'published_at' => $requiresGroupConfirmation ? null : now(),
            ]);

            if ($requiresGroupConfirmation) {
                $this->createGroupConfirmationsAndNotify($publication);
            }

            return $publication;
        });

        return redirect()->route('report-card-publications.index')->with(
            'success',
            $publication->isPublished()
                ? 'Rapor berhasil diterbitkan.'
                : 'Penerbitan rapor dikonfirmasi. Menunggu konfirmasi dari setiap kelompok sebelum rapor terbit.',
        );
    }

    /**
     * One confirmation per kelompok with active generus, notifying every user holding
     * confirm-report-card-publishing scoped to that kelompok.
     */
    private function createGroupConfirmationsAndNotify(ReportCardPublication $publication): void
    {
        $groupIds = Generus::where('status', 'active')
            ->whereHas('assignments', fn ($query) => $query->whereNull('ended_at')->whereNotNull('group_id'))
            ->with(['assignments' => fn ($query) => $query->whereNull('ended_at')])
            ->get()
            ->flatMap(fn (Generus $generus) => $generus->assignments->pluck('group_id'))
            ->filter()
            ->unique();

        $groups = Group::where('is_active', true)->whereIn('id', $groupIds)->get();

        $recipients = User::where('status', 'active')->with('roles.permissions')->get()
            ->filter(fn (User $user): bool => $user->hasPermission(ReportCardGroupConfirmation::CONFIRM_PERMISSION));

        foreach ($groups as $group) {
            $confirmation = ReportCardGroupConfirmation::create([
                'report_card_publication_id' => $publication->id,
                'group_id' => $group->id,
            ]);

            $groupRecipients = $recipients->filter(fn (User $user): bool => in_array(
                $group->id,
                $user->scopedPlacementIds(ReportCardGroupConfirmation::CONFIRM_PERMISSION)['group_id'] ?? [],
                true,
            ))->values();

            if ($groupRecipients->isNotEmpty()) {
                Notification::send($groupRecipients, new ReportCardPublicationRequested($confirmation));
            }
        }
    }
}
