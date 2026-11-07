<?php

namespace App\Http\Controllers;

use App\Models\Musyawaroh;
use App\Models\Notulen;
use App\Models\User;
use App\Notifications\NotulenFinalized;
use App\Support\MusyawarohHierarchy;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\Rule;
use Throwable;

class NotulenController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'content' => ['nullable', 'string'],
            'bucket' => ['nullable', 'string', 'max:255'],
            'meeting_date' => ['nullable', 'date'],
            'musyawaroh_id' => ['nullable', 'integer', Rule::exists('musyawarohs', 'id')],
            'action' => ['nullable', Rule::in(['draft', 'final'])],
        ]);

        $musyawaroh = isset($validated['musyawaroh_id'])
            ? Musyawaroh::findOrFail($validated['musyawaroh_id'])
            : null;

        abort_if($musyawaroh !== null && ! $musyawaroh->isVisibleTo($request->user()), 403);

        $notulen = Notulen::create([
            ...$validated,
            'content' => $validated['content'] ?? $validated['title'],
            'created_by_id' => $request->user()->id,
            'status' => Notulen::STATUS_DRAFT,
        ]);

        if (($validated['action'] ?? 'draft') === 'final') {
            $this->finalize($notulen, $request->user());
        }

        if ($musyawaroh !== null) {
            return redirect()->route('musyawaroh.show', $musyawaroh)->with('success', 'Notulen berhasil disimpan.');
        }

        return redirect()->route('tasks.index')->with('success', 'Notulen berhasil disimpan.');
    }

    public function update(Request $request, Notulen $notulen): RedirectResponse
    {
        abort_unless($notulen->isContentEditableBy($request->user()), 403);

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'content' => ['nullable', 'string'],
        ]);

        $notulen->update([
            'title' => $validated['title'],
            'content' => $validated['content'] ?? $validated['title'],
        ]);

        return back()->with('success', 'Notulen berhasil diperbarui.');
    }

    public function send(Request $request, Notulen $notulen): RedirectResponse
    {
        abort_unless($notulen->isContentEditableBy($request->user()), 403);

        $this->finalize($notulen, $request->user());

        return back()->with('success', 'Notulen berhasil dikirim.');
    }

    public function toggle(Request $request, Notulen $notulen): RedirectResponse
    {
        abort_unless($notulen->isCheckableBy($request->user()), 403);

        $isDone = ! $notulen->is_done;

        $notulen->update([
            'is_done' => $isDone,
            'checked_by_id' => $isDone ? $request->user()->id : null,
            'checked_at' => $isDone ? now() : null,
        ]);

        return back()->with('success', $isDone ? 'Notulen ditandai selesai.' : 'Tanda selesai dibatalkan.');
    }

    public function destroy(Request $request, Notulen $notulen): RedirectResponse
    {
        abort_unless($notulen->isEditableBy($request->user()), 403);

        $musyawaroh = $notulen->musyawaroh;
        $notulen->delete();

        if ($musyawaroh !== null) {
            return redirect()->route('musyawaroh.show', $musyawaroh)->with('success', 'Notulen berhasil dihapus.');
        }

        return redirect()->route('tasks.index')->with('success', 'Notulen berhasil dihapus.');
    }

    /**
     * Locks the notulen as final and sends it to everyone within the musyawaroh's reach -
     * both the attendees who were present and the pengurus who weren't.
     */
    private function finalize(Notulen $notulen, User $sentBy): void
    {
        $notulen->update(['status' => Notulen::STATUS_FINAL, 'sent_at' => now()]);

        $musyawaroh = $notulen->musyawaroh;

        if ($musyawaroh === null) {
            return;
        }

        $recipientIds = collect(MusyawarohHierarchy::allowedUserIds($musyawaroh->createdBy))
            ->merge($musyawaroh->attendees->pluck('user_id')->filter())
            ->unique()
            ->reject(fn (int $id): bool => $id === $sentBy->id)
            ->values()
            ->all();

        $recipients = User::whereIn('id', $recipientIds)->where('status', 'active')->get();

        try {
            Notification::send($recipients, new NotulenFinalized($notulen, $sentBy));
        } catch (Throwable $e) {
            Log::error('Gagal mengirim notifikasi notulen final.', [
                'notulen_id' => $notulen->id,
                'exception' => $e,
            ]);
        }
    }
}
