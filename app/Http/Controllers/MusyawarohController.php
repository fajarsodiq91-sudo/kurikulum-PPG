<?php

namespace App\Http\Controllers;

use App\Models\Musyawaroh;
use App\Models\MusyawarohAttendee;
use App\Models\Notulen;
use App\Models\User;
use App\Notifications\MusyawarohInvited;
use App\Notifications\MusyawarohNotulenSent;
use App\Support\MusyawarohHierarchy;
use App\Support\TaskHierarchy;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Throwable;

class MusyawarohController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();

        $musyawarohs = Musyawaroh::visibleTo($user)
            ->with(['createdBy', 'attendees', 'notulens'])
            ->latest('meeting_date')
            ->latest('id')
            ->get();

        return view('musyawaroh.index', [
            'musyawarohs' => $musyawarohs,
        ]);
    }

    public function create(Request $request): View
    {
        return view('musyawaroh.create', [
            'tree' => MusyawarohHierarchy::treeFor($request->user()),
            'defaultMeetingDate' => $request->string('meeting_date')->value() ?: null,
            'bucketSuggestions' => TaskHierarchy::bucketSuggestionsFor($request->user()),
            'defaultCollapsed' => $this->defaultCollapsedFor($request->user()),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $user = $request->user();
        $allowedUserIds = MusyawarohHierarchy::allowedUserIds($user);

        $validated = $request->validate([
            'title' => ['nullable', 'string', 'max:255'],
            'meeting_date' => ['nullable', 'date'],
            'start_time' => ['nullable', 'date_format:H:i'],
            'end_time' => ['nullable', 'date_format:H:i', 'after:start_time'],
            'location' => ['nullable', 'string', 'max:255'],
            'invitees' => ['nullable', 'array'],
            'invitees.*' => ['nullable', 'boolean'],
            'manual_names' => ['nullable', 'array'],
            'manual_names.*' => ['nullable', 'string', 'max:255'],
        ]);

        $musyawaroh = Musyawaroh::create([
            'title' => $validated['title'] ?? null,
            'meeting_date' => $validated['meeting_date'] ?? now()->toDateString(),
            'start_time' => $validated['start_time'] ?? null,
            'end_time' => $validated['end_time'] ?? null,
            'location' => $validated['location'] ?? null,
            'created_by_id' => $user->id,
        ]);

        $invitedUserIds = [];

        foreach ($validated['invitees'] ?? [] as $userId => $invited) {
            if (! $invited || ! in_array((int) $userId, $allowedUserIds, true)) {
                continue;
            }

            MusyawarohAttendee::create([
                'musyawaroh_id' => $musyawaroh->id,
                'user_id' => (int) $userId,
                'status' => MusyawarohAttendee::STATUS_DIUNDANG,
            ]);

            $invitedUserIds[] = (int) $userId;
        }

        foreach (array_filter($validated['manual_names'] ?? []) as $manualName) {
            MusyawarohAttendee::create([
                'musyawaroh_id' => $musyawaroh->id,
                'manual_name' => $manualName,
                'status' => MusyawarohAttendee::STATUS_DIUNDANG,
            ]);
        }

        if ($invitedUserIds !== []) {
            Notification::send(
                User::where('status', 'active')->whereIn('id', $invitedUserIds)->get(),
                new MusyawarohInvited($musyawaroh),
            );
        }

        return redirect()->route('musyawaroh.show', $musyawaroh)->with('success', 'Musyawaroh berhasil dibuat dan undangan terkirim.');
    }

    public function show(Request $request, Musyawaroh $musyawaroh): View
    {
        abort_unless($musyawaroh->isVisibleTo($request->user()), 403);

        return view('musyawaroh.show', [
            'musyawaroh' => $musyawaroh->load(['createdBy', 'attendees.user', 'notulens.createdBy', 'notulens.checkedBy', 'notulens.task.assignees']),
            'recipients' => TaskHierarchy::assignableRecipients($request->user()),
        ]);
    }

    public function updateAttendance(Request $request, Musyawaroh $musyawaroh): RedirectResponse
    {
        abort_unless($musyawaroh->isVisibleTo($request->user()), 403);

        $validated = $request->validate([
            'status' => ['required', 'array'],
            'status.*' => ['required', 'string', Rule::in(MusyawarohAttendee::STATUSES)],
        ]);

        $attendeeIds = array_map('intval', array_keys($validated['status']));

        $musyawaroh->attendees()
            ->whereIn('id', $attendeeIds)
            ->get()
            ->each(fn (MusyawarohAttendee $attendee) => $attendee->update(['status' => $validated['status'][$attendee->id]]));

        return redirect()->route('musyawaroh.show', $musyawaroh)->with('success', 'Daftar hadir berhasil diperbarui.');
    }

    /**
     * Sends every still-draft notulen of this musyawaroh out together as a single resume
     * email, instead of one email per item - and locks them all as final in one go. Items
     * already sent in an earlier round are left untouched, so this can be clicked again
     * later once more drafts have piled up.
     */
    public function sendNotulen(Request $request, Musyawaroh $musyawaroh): RedirectResponse
    {
        abort_unless($musyawaroh->isEditableBy($request->user()), 403);

        $drafts = $musyawaroh->notulens()->where('status', Notulen::STATUS_DRAFT)->get();

        if ($drafts->isEmpty()) {
            return back()->with('error', 'Tidak ada notulen draft untuk dikirim.');
        }

        $drafts->each(fn (Notulen $notulen) => $notulen->update(['status' => Notulen::STATUS_FINAL, 'sent_at' => now()]));

        $actor = $request->user();
        $recipientIds = collect(MusyawarohHierarchy::allowedUserIds($musyawaroh->createdBy))
            ->merge($musyawaroh->attendees->pluck('user_id')->filter())
            ->unique()
            ->reject(fn (int $id): bool => $id === $actor->id)
            ->values()
            ->all();

        $recipients = User::whereIn('id', $recipientIds)->where('status', 'active')->get();

        try {
            Notification::send($recipients, new MusyawarohNotulenSent($musyawaroh, $drafts, $actor));
        } catch (Throwable $e) {
            Log::error('Gagal mengirim resume notulen musyawaroh.', [
                'musyawaroh_id' => $musyawaroh->id,
                'exception' => $e,
            ]);
        }

        return back()->with('success', $drafts->count().' notulen berhasil dikirim sebagai 1 resume.');
    }

    /**
     * Daerah and desa trees span several branches, so they start collapsed to avoid an
     * overwhelming wall of names; a kelompok's tree is just their own small group, so it
     * starts open.
     */
    private function defaultCollapsedFor(User $user): bool
    {
        return $user->permissionScopes(TaskHierarchy::PERMISSION)
            ->contains(fn (array $scope): bool => in_array($scope['type'], ['global', 'region', 'village'], true));
    }
}
