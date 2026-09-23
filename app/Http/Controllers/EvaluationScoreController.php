<?php

namespace App\Http\Controllers;

use App\Models\Evaluation;
use App\Models\EvaluationScore;
use App\Models\Generus;
use Illuminate\Database\Eloquent\Builder;
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
            ...$this->formOptions(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        EvaluationScore::create($this->validateScore($request));

        return redirect()->route('evaluation-scores.index')->with('success', 'Skor evaluasi berhasil ditambahkan.');
    }

    public function edit(EvaluationScore $evaluationScore): View
    {
        return view('evaluation-scores.edit', [
            'score' => $evaluationScore,
            ...$this->formOptions($evaluationScore),
        ]);
    }

    public function update(Request $request, EvaluationScore $evaluationScore): RedirectResponse
    {
        $evaluationScore->update($this->validateScore($request, $evaluationScore));

        return redirect()->route('evaluation-scores.index')->with('success', 'Skor evaluasi berhasil diperbarui.');
    }

    public function destroy(EvaluationScore $evaluationScore): RedirectResponse
    {
        $evaluationScore->delete();

        return redirect()->route('evaluation-scores.index')->with('success', 'Skor evaluasi berhasil dihapus.');
    }

    /**
     * Active generus, plus the one already linked to the score being edited.
     *
     * @return array<string, mixed>
     */
    private function formOptions(?EvaluationScore $score = null): array
    {
        return [
            'evaluations' => Evaluation::latest()->get(),
            'generus' => Generus::where('status', 'active')
                ->when($score, fn (Builder $query) => $query->orWhere('id', $score->generus_id))
                ->get(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function validateScore(Request $request, ?EvaluationScore $score = null): array
    {
        return $request->validate([
            'evaluation_id' => ['required', 'exists:evaluations,id'],
            'generus_id' => [
                'required',
                'exists:generus,id',
                Rule::unique('evaluation_scores')
                    ->where('evaluation_id', $request->input('evaluation_id'))
                    ->ignore($score?->id),
            ],
            'score' => ['required', 'numeric', 'min:0', 'max:100'],
            'grade' => ['required', 'string', 'max:10'],
            'notes' => ['nullable', 'string'],
        ], [
            'generus_id.unique' => 'Generus ini sudah memiliki nilai untuk evaluasi tersebut.',
        ]);
    }
}
