<?php

namespace App\Http\Controllers;

use App\Models\Communication;
use App\Models\Training;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CommunicationController extends Controller
{
    public function index(): View
    {
        return view('communications.index', [
            'communications' => Communication::with('training')->latest()->paginate(25),
            'trainings' => Training::latest()->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'training_id' => ['nullable', 'exists:trainings,id'],
            'channel' => ['required', 'string', 'max:50'],
            'subject' => ['required', 'string', 'max:255'],
            'message' => ['required', 'string'],
            'status' => ['required', 'string', 'max:50'],
        ]);

        Communication::create($validated);

        return redirect()->route('communications.index')->with('success', 'Pesan komunikasi berhasil dikirim.');
    }
}
