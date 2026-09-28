<?php

namespace App\Http\Controllers\MasterData;

use App\Http\Controllers\Concerns\HandlesSheets;
use App\Http\Controllers\Controller;
use App\Models\AcademicYear;
use App\Models\ClassGrade;
use App\Models\Group;
use App\Models\Level;
use App\Models\Region;
use App\Models\Semester;
use App\Models\Village;
use App\Support\Sheets\AcademicYearSheet;
use App\Support\Sheets\ClassGradeSheet;
use App\Support\Sheets\GroupSheet;
use App\Support\Sheets\LevelSheet;
use App\Support\Sheets\SemesterSheet;
use App\Support\Sheets\Sheet;
use App\Support\Sheets\VillageSheet;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class MasterDataController extends Controller
{
    use HandlesSheets;

    public function index(): RedirectResponse
    {
        return redirect()->route('master-data.villages.index');
    }

    public function villages(): View
    {
        return view('master-data.villages', [
            'villages' => Village::query()->latest()->get(),
        ]);
    }

    public function groups(): View
    {
        return view('master-data.groups', [
            'villages' => Village::query()->get(),
            'groups' => Group::query()->with('village')->latest()->get(),
        ]);
    }

    public function levels(): View
    {
        return view('master-data.levels', [
            'levels' => Level::query()->orderBy('sort_order')->latest()->get(),
        ]);
    }

    public function classGrades(): View
    {
        return view('master-data.class-grades', [
            'classGrades' => ClassGrade::query()->orderBy('sort_order')->latest()->get(),
        ]);
    }

    public function academicYears(): View
    {
        return view('master-data.academic-years', [
            'academicYears' => AcademicYear::query()->latest()->get(),
        ]);
    }

    public function semesters(): View
    {
        return view('master-data.semesters', [
            'academicYears' => AcademicYear::query()->latest()->get(),
            'semesters' => Semester::query()->with('academicYear')->orderBy('sort_order')->latest()->get(),
        ]);
    }

    public function storeVillage(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'code' => ['nullable', 'string', 'max:50'],
            'is_active' => ['required', 'boolean'],
            'description' => ['nullable', 'string'],
        ]);
        Village::create([...$validated, 'region_id' => Region::karawangTimur()->id]);

        return $this->success('Desa berhasil ditambahkan.', 'villages');
    }

    public function updateVillage(Request $request, Village $village): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'code' => ['nullable', 'string', 'max:50'],
            'is_active' => ['required', 'boolean'],
            'description' => ['nullable', 'string'],
        ]);
        $village->update($validated);

        return $this->success('Desa berhasil diperbarui.', 'villages');
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

        return $this->success('Kelompok berhasil ditambahkan.', 'groups');
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

        return $this->success('Kelompok berhasil diperbarui.', 'groups');
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

        return $this->success('Jenjang berhasil ditambahkan.', 'levels');
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

        return $this->success('Jenjang berhasil diperbarui.', 'levels');
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

        return $this->success('Kelas berhasil ditambahkan.', 'class-grades');
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

        return $this->success('Kelas berhasil diperbarui.', 'class-grades');
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

        return $this->success('Tahun akademik berhasil ditambahkan.', 'academic-years');
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

        return $this->success('Tahun akademik berhasil diperbarui.', 'academic-years');
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

        return $this->success('Semester berhasil ditambahkan.', 'semesters');
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

        return $this->success('Semester berhasil diperbarui.', 'semesters');
    }

    public function export(string $page): BinaryFileResponse
    {
        return $this->exportSheet($this->sheetFor($page));
    }

    public function import(Request $request, string $page): RedirectResponse
    {
        return $this->importSheet($request, $this->sheetFor($page));
    }

    private function sheetFor(string $page): Sheet
    {
        return match ($page) {
            'villages' => new VillageSheet,
            'groups' => new GroupSheet,
            'levels' => new LevelSheet,
            'class-grades' => new ClassGradeSheet,
            'academic-years' => new AcademicYearSheet,
            'semesters' => new SemesterSheet,
            default => abort(404),
        };
    }

    private function success(string $message, string $page): RedirectResponse
    {
        return redirect()->route("master-data.{$page}.index")->with('success', $message);
    }
}
