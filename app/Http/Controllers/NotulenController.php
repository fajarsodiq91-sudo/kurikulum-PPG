<?php

namespace App\Http\Controllers;

use App\Models\Notulen;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class NotulenController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'content' => ['required', 'string'],
            'bucket' => ['nullable', 'string', 'max:255'],
            'meeting_date' => ['nullable', 'date'],
        ]);

        Notulen::create([
            ...$validated,
            'created_by_id' => $request->user()->id,
        ]);

        return redirect()->route('tasks.index')->with('success', 'Notulen berhasil disimpan.');
    }

    public function destroy(Request $request, Notulen $notulen): RedirectResponse
    {
        abort_unless($notulen->isEditableBy($request->user()), 403);

        $notulen->delete();

        return redirect()->route('tasks.index')->with('success', 'Notulen berhasil dihapus.');
    }
}
