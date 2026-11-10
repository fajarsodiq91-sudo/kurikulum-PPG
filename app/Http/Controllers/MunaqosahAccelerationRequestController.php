<?php

namespace App\Http\Controllers;

use App\Models\Generus;
use App\Models\Munaqosah;
use App\Models\MunaqosahAccelerationRequest;
use App\Support\MunaqosahAccelerationApproval;
use App\Support\StudentScope;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Pelaksana PPG Kelompok proposes an out-of-cycle munaqosah ("akselerasi") for one of their
 * own generus; Perwakilan PPG Desa or PPG Daerah approves or rejects it (see
 * MunaqosahAccelerationApproval for who may decide which request).
 */
class MunaqosahAccelerationRequestController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();
        $canPropose = $user->hasPermission(MunaqosahAccelerationRequest::PROPOSE_PERMISSION);

        $myGroupIds = $canPropose
            ? $user->scopedPlacementIds(MunaqosahAccelerationRequest::PROPOSE_PERMISSION)['group_id'] ?? []
            : [];

        $mine = $canPropose
            ? MunaqosahAccelerationRequest::with(['generus', 'decidedBy'])
                ->where('requested_by_id', $user->id)
                ->latest()
                ->get()
            : collect();

        $pending = MunaqosahAccelerationApproval::pendingFor($user)->with('generus', 'requestedBy')->latest()->get();

        return view('munaqosah-acceleration-requests.index', [
            'canPropose' => $canPropose,
            'candidates' => $canPropose
                ? Generus::where('status', 'active')
                    ->whereHas('assignments', fn ($query) => $query->whereNull('ended_at')->whereIn('group_id', $myGroupIds))
                    ->orderBy('full_name')
                    ->get()
                : collect(),
            'mine' => $mine,
            'pending' => $pending,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $user = $request->user();
        $myGroupIds = $user->scopedPlacementIds(MunaqosahAccelerationRequest::PROPOSE_PERMISSION)['group_id'] ?? [];

        $validated = $request->validate([
            'generus_id' => ['required', Rule::exists('generus', 'id')],
            'reason' => ['nullable', 'string', 'max:1000'],
        ]);

        $generus = Generus::whereKey($validated['generus_id'])
            ->whereHas('assignments', fn ($query) => $query->whereNull('ended_at')->whereIn('group_id', $myGroupIds))
            ->first();

        abort_if($generus === null, 403, 'Generus ini bukan generus kelompok Anda.');

        abort_if(
            MunaqosahAccelerationRequest::where('generus_id', $generus->id)->where('status', MunaqosahAccelerationRequest::STATUS_PENDING)->exists(),
            422,
            'Generus ini sudah memiliki pengajuan akselerasi yang masih menunggu keputusan.',
        );

        MunaqosahAccelerationRequest::create([
            'generus_id' => $generus->id,
            'class_grade_id' => $generus->learning_class_id,
            'requested_by_id' => $user->id,
            'reason' => $validated['reason'] ?? null,
            'status' => MunaqosahAccelerationRequest::STATUS_PENDING,
        ]);

        return redirect()->route('munaqosah-acceleration-requests.index')->with('success', 'Pengajuan akselerasi berhasil dikirim, menunggu persetujuan.');
    }

    public function approve(Request $request, MunaqosahAccelerationRequest $munaqosahAccelerationRequest): RedirectResponse
    {
        abort_unless(MunaqosahAccelerationApproval::canApprove($request->user(), $munaqosahAccelerationRequest), 403);

        $validated = $request->validate(['decision_notes' => ['nullable', 'string', 'max:1000']]);

        $munaqosahAccelerationRequest->update([
            'status' => MunaqosahAccelerationRequest::STATUS_APPROVED,
            'decided_by_id' => $request->user()->id,
            'decided_at' => now(),
            'decision_notes' => $validated['decision_notes'] ?? null,
        ]);

        return redirect()->route('munaqosah-acceleration-requests.index')->with('success', 'Pengajuan akselerasi disetujui.');
    }

    public function reject(Request $request, MunaqosahAccelerationRequest $munaqosahAccelerationRequest): RedirectResponse
    {
        abort_unless(MunaqosahAccelerationApproval::canApprove($request->user(), $munaqosahAccelerationRequest), 403);

        $validated = $request->validate(['decision_notes' => ['nullable', 'string', 'max:1000']]);

        $munaqosahAccelerationRequest->update([
            'status' => MunaqosahAccelerationRequest::STATUS_REJECTED,
            'decided_by_id' => $request->user()->id,
            'decided_at' => now(),
            'decision_notes' => $validated['decision_notes'] ?? null,
        ]);

        return redirect()->route('munaqosah-acceleration-requests.index')->with('success', 'Pengajuan akselerasi ditolak.');
    }

    /**
     * Records the akselerasi munaqosah's own result once the request is approved, promoting
     * the generus (via Munaqosah::promoteIfEligible()) when the decision is "naik". Filled by
     * whoever may manage that generus's munaqosah (normally the Pelaksana Kelompok who proposed it).
     */
    public function recordResult(Request $request, MunaqosahAccelerationRequest $munaqosahAccelerationRequest): RedirectResponse
    {
        abort_unless($munaqosahAccelerationRequest->isApproved(), 403, 'Pengajuan ini belum disetujui.');

        StudentScope::authorize($request->user(), Munaqosah::MANAGE_PERMISSION, $munaqosahAccelerationRequest->generus_id);

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'academic_year_id' => ['required', 'exists:academic_years,id'],
            'semester_id' => ['required', 'exists:semesters,id'],
            'score' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'decision' => ['required', Rule::in([Munaqosah::DECISION_NAIK, Munaqosah::DECISION_TINGGAL])],
            'notes' => ['nullable', 'string'],
        ]);

        $generus = $munaqosahAccelerationRequest->generus;

        Munaqosah::updateOrCreate(
            [
                'generus_id' => $generus->id,
                'academic_year_id' => $validated['academic_year_id'],
                'semester_id' => $validated['semester_id'],
                'type' => Munaqosah::TYPE_AKSELERASI,
            ],
            [
                'class_grade_id' => $generus->learning_class_id,
                'munaqosah_acceleration_request_id' => $munaqosahAccelerationRequest->id,
                'title' => $validated['title'],
                'score' => $validated['score'] ?? null,
                'decision' => $validated['decision'],
                'status' => 'completed',
                'notes' => $validated['notes'] ?? null,
            ],
        );

        return redirect()->route('munaqosah-acceleration-requests.index')->with('success', 'Hasil munaqosah akselerasi berhasil disimpan.');
    }
}
