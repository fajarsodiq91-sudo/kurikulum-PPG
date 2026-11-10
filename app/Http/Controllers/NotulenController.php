<?php

namespace App\Http\Controllers;

use App\Models\Musyawaroh;
use App\Models\Notulen;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

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
        ]);

        $musyawaroh = isset($validated['musyawaroh_id'])
            ? Musyawaroh::findOrFail($validated['musyawaroh_id'])
            : null;

        abort_if($musyawaroh !== null && ! $musyawaroh->isVisibleTo($request->user()), 403);

        Notulen::create([
            ...$validated,
            'content' => $validated['content'] ?? $validated['title'],
            'created_by_id' => $request->user()->id,
            'status' => Notulen::STATUS_DRAFT,
        ]);

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
}
