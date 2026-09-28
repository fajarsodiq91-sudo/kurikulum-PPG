<?php

namespace App\Http\Controllers;

use App\Models\Generus;
use App\Models\Guardian;
use App\Models\ParentCommunication;
use App\Support\StudentScope;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ParentCommunicationController extends Controller
{
    private const PERMISSION = 'manage-parent-communications';

    public function index(Request $request): View
    {
        $ids = StudentScope::ids($request->user(), self::PERMISSION);

        return view('parent-communications.index', [
            'communications' => StudentScope::limit(
                ParentCommunication::with(['generus', 'guardian', 'teacher'])->latest('communicated_at')->latest('id'),
                $ids,
            )->paginate(25),
            'generus' => StudentScope::limit(Generus::where('status', 'active')->orderBy('full_name'), $ids, 'id')->get(),
            'guardians' => Guardian::with('generus:id,full_name')
                ->whereHas('generus', fn ($query) => $ids === null ? $query : $query->whereIn('generus.id', $ids))
                ->orderBy('full_name')
                ->get(),
            'channels' => ParentCommunication::CHANNELS,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'generus_id' => ['required', StudentScope::existsRule($request->user(), self::PERMISSION)],
            'guardian_id' => [
                'nullable',
                Rule::exists('generus_guardian', 'guardian_id')->where('generus_id', $request->input('generus_id')),
            ],
            'channel' => ['required', Rule::in(ParentCommunication::CHANNELS)],
            'communicated_at' => ['required', 'date'],
            'subject' => ['nullable', 'string', 'max:255'],
            'message' => ['required', 'string'],
        ], [
            'guardian_id.exists' => 'Orang tua/wali yang dipilih bukan wali dari generus tersebut.',
        ]);

        ParentCommunication::create([...$validated, 'teacher_id' => $request->user()->teacher_id]);

        return redirect()->route('parent-communications.index')->with('success', 'Catatan komunikasi berhasil ditambahkan.');
    }
}
