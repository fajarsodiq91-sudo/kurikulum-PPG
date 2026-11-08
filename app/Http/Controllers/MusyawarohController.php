<?php

namespace App\Http\Controllers;

use App\Models\Musyawaroh;
use App\Models\MusyawarohAttendee;
use App\Models\User;
use App\Notifications\MusyawarohInvited;
use App\Support\MusyawarohHierarchy;
use App\Support\TaskHierarchy;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

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
            'attendance' => ['nullable', 'array'],
            'attendance.*' => ['nullable', 'string', Rule::in(MusyawarohAttendee::STATUSES)],
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

        foreach ($validated['attendance'] ?? [] as $userId => $status) {
            if (blank($status) || ! in_array((int) $userId, $allowedUserIds, true)) {
                continue;
            }

            MusyawarohAttendee::create([
                'musyawaroh_id' => $musyawaroh->id,
                'user_id' => (int) $userId,
                'status' => $status,
            ]);

            $invitedUserIds[] = (int) $userId;
        }

        foreach (array_filter($validated['manual_names'] ?? []) as $manualName) {
            MusyawarohAttendee::create([
                'musyawaroh_id' => $musyawaroh->id,
                'manual_name' => $manualName,
                'status' => MusyawarohAttendee::STATUS_HADIR,
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
