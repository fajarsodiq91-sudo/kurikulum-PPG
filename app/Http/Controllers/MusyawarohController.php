<?php

namespace App\Http\Controllers;

use App\Models\Musyawaroh;
use App\Models\MusyawarohAttendee;
use App\Support\MusyawarohHierarchy;
use App\Support\TaskHierarchy;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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
            'bucketSuggestions' => TaskHierarchy::bucketSuggestionsFor($request->user()),
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
            'attendee_user_ids' => ['nullable', 'array'],
            'attendee_user_ids.*' => ['integer', Rule::in($allowedUserIds)],
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

        foreach (array_unique($validated['attendee_user_ids'] ?? []) as $attendeeId) {
            MusyawarohAttendee::create([
                'musyawaroh_id' => $musyawaroh->id,
                'user_id' => $attendeeId,
            ]);
        }

        foreach (array_filter($validated['manual_names'] ?? []) as $manualName) {
            MusyawarohAttendee::create([
                'musyawaroh_id' => $musyawaroh->id,
                'manual_name' => $manualName,
            ]);
        }

        return redirect()->route('musyawaroh.show', $musyawaroh)->with('success', 'Musyawaroh berhasil dibuat.');
    }

    public function show(Request $request, Musyawaroh $musyawaroh): View
    {
        abort_unless($musyawaroh->isVisibleTo($request->user()), 403);

        return view('musyawaroh.show', [
            'musyawaroh' => $musyawaroh->load(['createdBy', 'attendees.user', 'notulens.createdBy', 'notulens.checkedBy', 'notulens.task.assignees']),
            'recipients' => TaskHierarchy::assignableRecipients($request->user()),
        ]);
    }
}
