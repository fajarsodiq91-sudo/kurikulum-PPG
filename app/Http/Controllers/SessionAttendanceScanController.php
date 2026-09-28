<?php

namespace App\Http\Controllers;

use App\Models\Generus;
use App\Models\LearningSession;
use App\Models\SessionAttendance;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Endpoints behind the attendance page's scanners. Every lookup is limited to the session's
 * roster, so a scan can never record someone the user could not tick by hand.
 */
class SessionAttendanceScanController extends Controller
{
    public function store(Request $request, LearningSession $learningSession): JsonResponse
    {
        $this->authorizeSession($learningSession);

        $validated = $request->validate([
            'method' => ['required', Rule::in([SessionAttendance::METHOD_QR, SessionAttendance::METHOD_RFID, SessionAttendance::METHOD_FACE])],
            'code' => ['required_unless:method,'.SessionAttendance::METHOD_FACE, 'nullable', 'string', 'max:100'],
            'generus_id' => ['required_if:method,'.SessionAttendance::METHOD_FACE, 'nullable', 'integer'],
        ]);

        $roster = $learningSession->rosterFor($request->user());

        $generus = match ($validated['method']) {
            SessionAttendance::METHOD_QR => $roster->where('registration_number', trim($validated['code']))->first(),
            SessionAttendance::METHOD_RFID => $roster->where('rfid_uid', Generus::normalizeRfidUid($validated['code']))->first(),
            default => $roster->whereKey($validated['generus_id'])->first(),
        };

        if ($generus === null) {
            return response()->json(['message' => 'Generus tidak ditemukan di daftar hadir sesi ini.'], 404);
        }

        $attendance = $learningSession->attendances()->firstOrNew(['generus_id' => $generus->id]);
        $alreadyPresent = $attendance->exists && $attendance->status === 'present';

        if (! $alreadyPresent) {
            $attendance->fill(['status' => 'present', 'method' => $validated['method']])->save();
        }

        return response()->json([
            'generus_id' => $generus->id,
            'name' => $generus->full_name,
            'already_present' => $alreadyPresent,
        ]);
    }

    /**
     * Roster members with a photo, for the in-browser face matcher.
     */
    public function faces(Request $request, LearningSession $learningSession): JsonResponse
    {
        $this->authorizeSession($learningSession);

        return response()->json($learningSession->rosterFor($request->user())
            ->whereNotNull('photo')
            ->get(['id', 'full_name'])
            ->map(fn (Generus $generus): array => [
                'id' => $generus->id,
                'name' => $generus->full_name,
                'photo' => route('learning-sessions.attendance.photo', [$learningSession, $generus]),
            ])
            ->values());
    }

    public function photo(Request $request, LearningSession $learningSession, int $generus): StreamedResponse
    {
        $this->authorizeSession($learningSession);

        $member = $learningSession->rosterFor($request->user())->whereKey($generus)->firstOrFail();

        abort_if(blank($member->photo) || ! Storage::exists($member->photo), 404);

        return Storage::response($member->photo);
    }

    private function authorizeSession(LearningSession $session): void
    {
        abort_unless($session->isAttendableBy(request()->user()), 404);
    }
}
