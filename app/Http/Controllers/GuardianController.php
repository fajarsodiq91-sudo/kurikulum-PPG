<?php

namespace App\Http\Controllers;

use App\Models\Guardian;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class GuardianController extends Controller
{
    public function index(): View
    {
        return view('guardians.index', [
            'guardians' => Guardian::latest()->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'full_name' => ['required', 'string', 'max:255'],
            'relationship' => ['required', 'string', 'max:100'],
            'phone' => ['nullable', 'string', 'max:50'],
            'address' => ['nullable', 'string'],
            'status' => ['required', 'string', 'max:50'],
            'notes' => ['nullable', 'string'],
        ]);

        Guardian::create($validated);

        return redirect()->route('guardians.index')->with('success', 'Data orang tua/wali berhasil ditambahkan.');
    }
}
