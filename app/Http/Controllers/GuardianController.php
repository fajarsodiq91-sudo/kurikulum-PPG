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
            'guardians' => Guardian::latest()->paginate(25),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        Guardian::create($this->validateGuardian($request));

        return redirect()->route('guardians.index')->with('success', 'Data orang tua/wali berhasil ditambahkan.');
    }

    public function edit(Guardian $guardian): View
    {
        return view('guardians.edit', ['guardian' => $guardian]);
    }

    public function update(Request $request, Guardian $guardian): RedirectResponse
    {
        $guardian->update($this->validateGuardian($request));

        return redirect()->route('guardians.index')->with('success', 'Data orang tua/wali berhasil diperbarui.');
    }

    public function destroy(Guardian $guardian): RedirectResponse
    {
        $guardian->delete();

        return redirect()->route('guardians.index')->with('success', 'Data orang tua/wali berhasil dihapus.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validateGuardian(Request $request): array
    {
        return $request->validate([
            'full_name' => ['required', 'string', 'max:255'],
            'relationship' => ['required', 'string', 'max:100'],
            'phone' => ['nullable', 'string', 'max:50'],
            'address' => ['nullable', 'string'],
            'status' => ['required', 'string', 'max:50'],
            'notes' => ['nullable', 'string'],
        ]);
    }
}
