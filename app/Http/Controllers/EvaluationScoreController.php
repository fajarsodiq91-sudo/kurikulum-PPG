<?php

namespace App\Http\Controllers;

use App\Models\Evaluation;
use App\Models\EvaluationScore;
use App\Models\Generus;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class EvaluationScoreController extends Controller
{
    public function index(): View
    {
        return view('evaluation-scores.index', [
            'scores' => EvaluationScore::with(['evaluation', 'generus'])->latest()->paginate(25),
            'evaluations' => Evaluation::latest()->get(),
            'generus' => Generus::where('status', 'active')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'evaluation_id' => ['required', 'exists:evaluations,id'],
            'generus_id' => [
                'required',
                'exists:generus,id',
                Rule::unique('evaluation_scores')->where('evaluation_id', $request->input('evaluation_id')),
            ],
            'score' => ['required', 'numeric', 'min:0', 'max:100'],
            'grade' => ['required', 'string', 'max:10'],
            'notes' => ['nullable', 'string'],
        ], [
            'generus_id.unique' => 'Generus ini sudah memiliki nilai untuk evaluasi tersebut.',
        ]);

        EvaluationScore::create($validated);

        return redirect()->route('evaluation-scores.index')->with('success', 'Skor evaluasi berhasil ditambahkan.');
    }
}
