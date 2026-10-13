<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\HandlesSheets;
use App\Models\Communication;
use App\Models\Training;
use App\Support\Sheets\CommunicationSheet;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class CommunicationController extends Controller
{
    use HandlesSheets;

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

    public function export(): BinaryFileResponse
    {
        return $this->exportSheet(new CommunicationSheet);
    }

    public function import(Request $request): RedirectResponse
    {
        return $this->importSheet($request, new CommunicationSheet);
    }
}
