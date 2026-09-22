<?php

namespace App\Http\Controllers;

use App\Exports\GenerusExport;
use App\Imports\GenerusImport;
use App\Models\AcademicYear;
use App\Models\Generus;
use App\Models\GenerusAssignment;
use App\Models\Group;
use App\Models\Level;
use App\Models\Region;
use App\Models\Village;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class GenerusController extends Controller
{
    public function index(): View
    {
        return view('generus.index', [
            'generus' => Generus::with('assignments')->latest()->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'registration_number' => ['required', 'string', 'max:50', 'unique:generus,registration_number'],
            'record_number' => ['nullable', 'string', 'max:50'],
            'full_name' => ['required', 'string', 'max:255'],
            'school_name' => ['nullable', 'string', 'max:255'],
            'nis' => ['nullable', 'string', 'max:50'],
            'father_name' => ['nullable', 'string', 'max:255'],
            'mother_name' => ['nullable', 'string', 'max:255'],
            'father_occupation' => ['nullable', 'string', 'max:255'],
            'mother_occupation' => ['nullable', 'string', 'max:255'],
            'phone_number' => ['nullable', 'string', 'max:50'],
            'gender' => ['nullable', 'string', 'max:50'],
            'birth_place' => ['nullable', 'string', 'max:255'],
            'birth_date' => ['nullable', 'date'],
            'birth_order' => ['nullable', 'integer', 'min:1', 'max:32767'],
            'sibling_count' => ['nullable', 'integer', 'min:0', 'max:32767'],
            'school_grade' => ['nullable', 'string', 'max:50'],
            'learning_class' => ['nullable', 'string', 'max:100'],
            'educational_level' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', 'string', 'max:50'],
            'region_id' => ['required', Rule::exists('regions', 'id')->where('is_active', true)],
            'village_id' => [
                'required',
                Rule::exists('villages', 'id')
                    ->where('region_id', $request->input('region_id'))
                    ->where('is_active', true),
            ],
            'group_id' => [
                'required',
                Rule::exists('groups', 'id')
                    ->where('village_id', $request->input('village_id'))
                    ->where('is_active', true),
            ],
            'level_id' => ['required', Rule::exists('levels', 'id')->where('is_active', true)],
            'academic_year_id' => ['required', Rule::exists('academic_years', 'id')->where('is_active', true)],
            'assignment_status' => ['required', 'string', 'max:50'],
            'notes' => ['nullable', 'string'],
        ]);

        DB::transaction(function () use ($validated): void {
            $generus = Generus::create([
                'registration_number' => $validated['registration_number'],
                'record_number' => $validated['record_number'] ?? null,
                'full_name' => $validated['full_name'],
                'school_name' => $validated['school_name'] ?? null,
                'nis' => $validated['nis'] ?? null,
                'father_name' => $validated['father_name'] ?? null,
                'mother_name' => $validated['mother_name'] ?? null,
                'father_occupation' => $validated['father_occupation'] ?? null,
                'mother_occupation' => $validated['mother_occupation'] ?? null,
                'phone_number' => $validated['phone_number'] ?? null,
                'gender' => $validated['gender'] ?? null,
                'birth_place' => $validated['birth_place'] ?? null,
                'birth_date' => $validated['birth_date'] ?? null,
                'birth_order' => $validated['birth_order'] ?? null,
                'sibling_count' => $validated['sibling_count'] ?? null,
                'school_grade' => $validated['school_grade'] ?? null,
                'learning_class' => $validated['learning_class'] ?? null,
                'educational_level' => $validated['educational_level'] ?? null,
                'status' => $validated['status'] ?? 'active',
                'notes' => $validated['notes'] ?? null,
            ]);

            GenerusAssignment::create([
                'generus_id' => $generus->id,
                'region_id' => $validated['region_id'],
                'village_id' => $validated['village_id'],
                'group_id' => $validated['group_id'],
                'level_id' => $validated['level_id'],
                'academic_year_id' => $validated['academic_year_id'],
                'status' => $validated['assignment_status'],
                'assigned_at' => now()->toDateString(),
                'notes' => $validated['notes'] ?? null,
            ]);
        });

        return redirect()->route('generus.index')->with('success', 'Generus berhasil ditambahkan.');
    }

    public function create(): View
    {
        return view('generus.create', [
            'regions' => Region::where('is_active', true)->get(),
            'villages' => Village::where('is_active', true)->get(),
            'groups' => Group::where('is_active', true)->get(),
            'levels' => Level::where('is_active', true)->orderBy('sort_order')->get(),
            'academicYears' => AcademicYear::where('is_active', true)->get(),
        ]);
    }

    public function import(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'file' => ['required', 'file', 'mimes:xlsx', 'max:10240'],
        ]);

        Excel::import(new GenerusImport, $validated['file']);

        return redirect()->route('generus.index')->with('success', 'Data generus berhasil diimpor dari XLSX.');
    }

    public function export(): BinaryFileResponse
    {
        return Excel::download(new GenerusExport, 'generus.xlsx');
    }
}
