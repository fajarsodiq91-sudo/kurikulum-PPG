<?php

namespace App\Http\Controllers\MasterData;

use App\Http\Controllers\Controller;
use App\Models\AcademicYear;
use App\Models\Group;
use App\Models\Level;
use App\Models\Region;
use App\Models\Semester;
use App\Models\Village;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class MasterDataController extends Controller
{
    public function index(): View
    {
        return view('master-data.index', [
            'regions' => Region::query()->latest()->get(),
            'villages' => Village::query()->with('region')->latest()->get(),
            'groups' => Group::query()->with('village.region')->latest()->get(),
            'levels' => Level::query()->orderBy('sort_order')->latest()->get(),
            'academicYears' => AcademicYear::query()->latest()->get(),
            'semesters' => Semester::query()->with('academicYear')->orderBy('sort_order')->latest()->get(),
        ]);
    }

    public function storeRegion(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:50', 'unique:regions,code'],
            'is_active' => ['required', 'boolean'],
            'description' => ['nullable', 'string'],
        ]);
        Region::create($validated);

        return $this->success('Daerah berhasil ditambahkan.');
    }

    public function storeVillage(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'region_id' => ['required', Rule::exists('regions', 'id')->where('is_active', true)],
            'name' => ['required', 'string', 'max:255'],
            'code' => ['nullable', 'string', 'max:50'],
            'is_active' => ['required', 'boolean'],
            'description' => ['nullable', 'string'],
        ]);
        Village::create($validated);

        return $this->success('Desa berhasil ditambahkan.');
    }

    public function storeGroup(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'village_id' => ['required', Rule::exists('villages', 'id')->where('is_active', true)],
            'name' => ['required', 'string', 'max:255'],
            'code' => ['nullable', 'string', 'max:50'],
            'is_active' => ['required', 'boolean'],
            'description' => ['nullable', 'string'],
        ]);
        Group::create($validated);

        return $this->success('Kelompok berhasil ditambahkan.');
    }

    public function storeLevel(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:50', 'unique:levels,code'],
            'sort_order' => ['required', 'integer', 'min:0'],
            'is_active' => ['required', 'boolean'],
            'description' => ['nullable', 'string'],
        ]);
        Level::create($validated);

        return $this->success('Jenjang berhasil ditambahkan.');
    }

    public function storeAcademicYear(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:50', 'unique:academic_years,code'],
            'start_year' => ['required', 'integer', 'digits:4'],
            'end_year' => ['required', 'integer', 'digits:4', 'gte:start_year'],
            'is_active' => ['required', 'boolean'],
        ]);
        AcademicYear::create($validated);

        return $this->success('Tahun akademik berhasil ditambahkan.');
    }

    public function storeSemester(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'academic_year_id' => ['required', Rule::exists('academic_years', 'id')->where('is_active', true)],
            'name' => ['required', 'string', 'max:255'],
            'code' => ['nullable', 'string', 'max:50'],
            'sort_order' => ['required', 'integer', 'min:0'],
            'is_active' => ['required', 'boolean'],
        ]);
        Semester::create($validated);

        return $this->success('Semester berhasil ditambahkan.');
    }

    private function success(string $message): RedirectResponse
    {
        return redirect()->route('master-data.index')->with('success', $message);
    }
}
