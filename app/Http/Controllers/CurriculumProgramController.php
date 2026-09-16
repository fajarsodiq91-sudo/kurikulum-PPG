<?php

namespace App\Http\Controllers;

use App\Models\CurriculumProgram;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CurriculumProgramController extends Controller
{
    public function index(): View
    {
        return view('curriculum-programs.index', [
            'programs' => CurriculumProgram::latest()->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:50', 'unique:curriculum_programs,code'],
            'description' => ['nullable', 'string'],
            'is_active' => ['required', 'boolean'],
        ]);

        CurriculumProgram::create($validated);

        return redirect()->route('curriculum-programs.index')->with('success', 'Program kurikulum berhasil ditambahkan.');
    }
}
