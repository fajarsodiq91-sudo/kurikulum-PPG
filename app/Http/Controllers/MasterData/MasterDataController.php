<?php

namespace App\Http\Controllers\MasterData;

use App\Http\Controllers\Controller;
use App\Models\AcademicYear;
use App\Models\ClassGrade;
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
            'classGrades' => ClassGrade::query()->orderBy('sort_order')->latest()->get(),
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

    public function updateRegion(Request $request, Region $region): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:50', Rule::unique('regions', 'code')->ignore($region->id)],
            'is_active' => ['required', 'boolean'],
            'description' => ['nullable', 'string'],
        ]);
        $region->update($validated);

        return $this->success('Daerah berhasil diperbarui.');
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

    public function updateVillage(Request $request, Village $village): RedirectResponse
    {
        $validated = $request->validate([
            'region_id' => ['required', Rule::exists('regions', 'id')->where('is_active', true)],
            'name' => ['required', 'string', 'max:255'],
            'code' => ['nullable', 'string', 'max:50'],
            'is_active' => ['required', 'boolean'],
            'description' => ['nullable', 'string'],
        ]);
        $village->update($validated);

        return $this->success('Desa berhasil diperbarui.');
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

    public function updateGroup(Request $request, Group $group): RedirectResponse
    {
        $validated = $request->validate([
            'village_id' => ['required', Rule::exists('villages', 'id')->where('is_active', true)],
            'name' => ['required', 'string', 'max:255'],
            'code' => ['nullable', 'string', 'max:50'],
            'is_active' => ['required', 'boolean'],
            'description' => ['nullable', 'string'],
        ]);
        $group->update($validated);

        return $this->success('Kelompok berhasil diperbarui.');
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

    public function updateLevel(Request $request, Level $level): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:50', Rule::unique('levels', 'code')->ignore($level->id)],
            'sort_order' => ['required', 'integer', 'min:0'],
            'is_active' => ['required', 'boolean'],
            'description' => ['nullable', 'string'],
        ]);
        $level->update($validated);

        return $this->success('Jenjang berhasil diperbarui.');
    }

    public function storeClassGrade(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:50', 'unique:class_grades,code'],
            'sort_order' => ['required', 'integer', 'min:0'],
            'is_active' => ['required', 'boolean'],
            'description' => ['nullable', 'string'],
        ]);
        ClassGrade::create($validated);

        return $this->success('Kelas berhasil ditambahkan.');
    }

    public function updateClassGrade(Request $request, ClassGrade $classGrade): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:50', Rule::unique('class_grades', 'code')->ignore($classGrade->id)],
            'sort_order' => ['required', 'integer', 'min:0'],
            'is_active' => ['required', 'boolean'],
            'description' => ['nullable', 'string'],
        ]);
        $classGrade->update($validated);

        return $this->success('Kelas berhasil diperbarui.');
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

    public function updateAcademicYear(Request $request, AcademicYear $academicYear): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:50', Rule::unique('academic_years', 'code')->ignore($academicYear->id)],
            'start_year' => ['required', 'integer', 'digits:4'],
            'end_year' => ['required', 'integer', 'digits:4', 'gte:start_year'],
            'is_active' => ['required', 'boolean'],
        ]);
        $academicYear->update($validated);

        return $this->success('Tahun akademik berhasil diperbarui.');
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

    public function updateSemester(Request $request, Semester $semester): RedirectResponse
    {
        $validated = $request->validate([
            'academic_year_id' => ['required', Rule::exists('academic_years', 'id')->where('is_active', true)],
            'name' => ['required', 'string', 'max:255'],
            'code' => ['nullable', 'string', 'max:50'],
            'sort_order' => ['required', 'integer', 'min:0'],
            'is_active' => ['required', 'boolean'],
        ]);
        $semester->update($validated);

        return $this->success('Semester berhasil diperbarui.');
    }

    private function success(string $message): RedirectResponse
    {
        return redirect()->route('master-data.index')->with('success', $message);
    }
}
