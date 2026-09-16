<?php

namespace App\Http\Controllers;

use App\Models\Evaluation;
use App\Models\EvaluationScore;
use App\Models\Generus;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class EvaluationScoreController extends Controller
{
    public function index(): View
    {
        return view('evaluation-scores.index', [
            'scores' => EvaluationScore::with(['evaluation', 'generus'])->latest()->get(),
            'evaluations' => Evaluation::latest()->get(),
            'generus' => Generus::where('status', 'active')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'evaluation_id' => ['required', 'exists:evaluations,id'],
            'generus_id' => ['required', 'exists:generus,id'],
            'score' => ['required', 'numeric', 'min:0', 'max:100'],
            'grade' => ['required', 'string', 'max:10'],
            'notes' => ['nullable', 'string'],
        ]);

        EvaluationScore::create($validated);

        return redirect()->route('evaluation-scores.index')->with('success', 'Skor evaluasi berhasil ditambahkan.');
    }
}
