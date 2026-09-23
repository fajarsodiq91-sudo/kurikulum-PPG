<?php

namespace App\Http\Controllers\MasterData;

use App\Http\Controllers\Controller;
use App\Models\Region;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class RegionController extends Controller
{
    public function index(): View
    {
        return view('master-data.regions.index', [
            'regions' => Region::latest()->paginate(25),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:50', 'unique:regions,code'],
            'is_active' => ['required', 'boolean'],
            'description' => ['nullable', 'string'],
        ]);

        Region::create($validated);

        return redirect()->route('master-data.regions.index')->with('success', 'Daerah berhasil ditambahkan.');
    }
}
