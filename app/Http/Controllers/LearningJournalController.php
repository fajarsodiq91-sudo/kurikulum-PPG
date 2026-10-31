<?php

namespace App\Http\Controllers;

use App\Models\LearningSession;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class LearningJournalController extends Controller
{
    public function update(Request $request, LearningSession $learningSession): RedirectResponse
    {
        abort_unless($learningSession->isAttendableBy($request->user()), 404);

        $validated = $request->validate([
            'material_id' => ['nullable', 'exists:learning_materials,id'],
            'content' => ['nullable', 'string'],
        ]);

        $learningSession->journal()->updateOrCreate([], $validated);

        return redirect()->route('learning-sessions.attendance.edit', $learningSession)->with('success', 'Jurnal KBM berhasil disimpan.');
    }
}
