<?php

namespace App\Http\Controllers;

use App\Models\OrganizationUnit;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class OrganizationUnitController extends Controller
{
    public function index(): View
    {
        return view('organization-units.index', [
            'organizationUnits' => OrganizationUnit::latest()->paginate(25),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:50', 'unique:organization_units,code'],
            'leader_name' => ['nullable', 'string', 'max:255'],
            'unit_type' => ['required', 'string', 'max:50'],
            'status' => ['required', 'string', 'max:50'],
            'description' => ['nullable', 'string'],
        ]);

        OrganizationUnit::create($validated);

        return redirect()->route('organization-units.index')->with('success', 'Unit organisasi berhasil ditambahkan.');
    }
}
